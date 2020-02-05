from sklearn import datasets
from sklearn.dummy import DummyClassifier, DummyRegressor
from sklearn.model_selection import GridSearchCV
from sklearn.model_selection import train_test_split
import pandas as pd
import csv


def write_train_test(data, filename_prefix, test_size=0.2, regression=False, scoring=None):
    if scoring is None:
        scoring = 'max_error' if regression else 'accuracy'
    if regression:
        stratify = pd.qcut(data.target, q=10).codes
    else:
        stratify = data.target

    X_train, X_test, y_train, y_test = train_test_split(data.data, data.target, test_size=test_size,
                                                        random_state=42, stratify=stratify)

    with open(filename_prefix + '_train.csv', 'w', newline='') as csv_file:
        writer = csv.writer(csv_file)
        writer.writerow(list(data.feature_names) + ['target'])
        if regression:
            writer.writerows([(*features, y) for features, y in zip(X_train,y_train)])
        else:
            writer.writerows([(*features, data.target_names[y]) for features, y in zip(X_train, y_train)])

    with open(filename_prefix + '_test_features.csv', 'w', newline='') as csv_file:
        writer = csv.writer(csv_file)
        writer.writerow(data.feature_names)
        writer.writerows(X_test)

    with open(filename_prefix + '_test_gold.csv', 'w', newline='') as csv_file:
        writer = csv.writer(csv_file)
        writer.writerow(['target'])
        if regression:
            writer.writerows([[y] for y in y_test])
        else:
            writer.writerows([[data.target_names[y]] for y in y_test])

    #baseline
    if regression:
        clf = GridSearchCV(DummyRegressor(), {'strategy': ('mean', 'median')}, scoring=scoring, cv=10)
    else:
        clf = GridSearchCV(DummyClassifier(), {'strategy':('stratified', 'most_frequent', 'uniform')}, scoring=scoring, cv=10)
    clf.fit(X_train, y_train)
    print("Best baseline is: " + str(clf.best_params_))
    with open(filename_prefix + '_baseline.csv', 'w', newline='') as csv_file:
        writer = csv.writer(csv_file)
        writer.writerow(['target'])
        if regression:
            writer.writerows([[y] for y in clf.predict(X_test)])
        else:
            writer.writerows([[data.target_names[y]] for y in clf.predict(X_test)])
    print("baseline score ({}) on test data is: {}".format(scoring, clf.score(X_test, y_test)))


write_train_test(datasets.load_iris(), 'classificaton_iris')
#write_train_test(datasets.load_wine(), 'classificaton_wine')
#write_train_test(datasets.load_breast_cancer(), 'classificaton_cancer')

#write_train_test(datasets.load_diabetes(), 'regression_diabetes', regression=True)
#write_train_test(datasets.load_boston(), 'regression_boston', regression=True)

