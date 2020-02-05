#!/usr/bin/env python3
# run it with python3 and call it similar to "python restEndpoint.py"
# test with (check gold standard): curl -F 'removeHeader=True' -F 'gold=@iris_test_class.csv' http://127.0.0.1:41193/metric/precision_micro
# test with (compute measure)    : curl -F 'removeHeader=True' -F 'gold=@iris_test_class.csv' -F 'system=@student_v3.csv' http://127.0.0.1:41193/metric/precision_micro

#regression: curl -F 'removeHeader=True' -F 'gold=@regression_test_class.csv' -F 'system=@regression_student.csv' http://127.0.0.1:41193/metric/max_error

import csv
from flask import Flask, request, abort
from werkzeug.exceptions import HTTPException
from sklearn.metrics import get_scorer
import numpy as np

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 64 * 1024 * 1024  # restrict to maximum file size of 64 MB


@app.route('/')
def index():
    return 'Metric Server Works!'


@app.route('/metric/<scoring>', methods=['POST'])
def metric(scoring):
    print("FORM:" + str(request.form))
    print("FORM:" + str(request.files))
    if 'removeHeader' not in request.form:
        abort(400, description="Parameter 'removeHeader' is not provided")
    remove_header = request.form['removeHeader'].strip().lower() == "true"

    if 'gold' not in request.files:
        abort(400, description="Gold standard is not provided")
    
    gold = _parse_csv(request.files['gold'], remove_header)
    distinct_gold = set(gold)
    if 'system' not in request.files:
        # just check gold standard
        if len(gold) == len(distinct_gold):
            abort(400, description="Each example of the gold standard has a different class - "
                                   "this is probably not intended and is more a parsing error.")
        return {"description": "Parsed {} examples with {} distinct values.".format(len(gold), len(distinct_gold))}, 200
    
    # check system file
    system = _parse_csv(request.files['system'], False)
    checked_system = []
    for i, system_result in enumerate(system):
        if i == 0:
            continue
        if system_result not in distinct_gold:
            if i == 0:
                continue  # silently skip header
            abort(400, description='Error when parsing csv: "{}" is not one of {}'
                  .format(system_result, distinct_gold))
        checked_system.append(system_result)
    if len(checked_system) != len(gold):
        abort(400, description="Number of values in csv does not match the gold standard. "
                               "Csv has {} valid example(s) whereas gold standard has {} examples. Check csv file."
              .format(len(checked_system),len(gold)))
    
    # compute actual value
    # choose one from https://scikit-learn.org/stable/modules/model_evaluation.html
    scorer = get_scorer(scoring)
    # access to a protected member of a class, but otherwise we have to import all scorers
    try:
        value = scorer._sign * scorer._score_func(np.array(gold), np.array(checked_system), **scorer._kwargs)
    except Exception as inst:
        abort(400, description=str(inst))
    
    return {"points": value, "description": "You got " + value}, 200


def _parse_csv(file_storage_obj, remove_header):
    # used splitlines because of universal newlines (unix line endings)
    lines = file_storage_obj.read().decode('utf-8').splitlines()
    reader = csv.reader(lines)
    if remove_header:
        next(reader)
    values = []
    for row in reader:
        for cell in reversed(row):
            stripped_cell = cell.strip()
            if stripped_cell:
                values.append(stripped_cell)
                break
    return values


# https://flask.palletsprojects.com/en/1.1.x/patterns/errorpages/
# https://flask.palletsprojects.com/en/1.1.x/errorhandling/
@app.errorhandler(HTTPException)
def handle_exception(e):
    """Return JSON instead of HTML for HTTP errors."""
    return {'error': {'message': e.description, 'code': e.code}}, e.code


@app.errorhandler(Exception)
def handle_exception(e):
    """Return JSON instead of HTML for general errors."""
    return {'error': {'message': str(e), 'code': 500}}, 500


if __name__ == "__main__":
    app.run(debug=True, port=41193)
