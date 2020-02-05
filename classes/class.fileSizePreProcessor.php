<?php

use ILIAS\FileUpload\Processor\PreProcessor;
use ILIAS\Filesystem\Stream\FileStream;
use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\FileUpload\DTO\ProcessingStatus;

/**
 * Class FileSizePreProcessor
 *
 * PreProcessor which denies the file if is is too large.
 * @author Sven Hertling <sven@informatik.uni-mannheim.de>
 * @author Sebastian Kotthoff <sebastian.kotthoff@uni-mannheim.de>
 * @author Nicolas Heist <nico@informatik.uni-mannheim.de>
 *
 */
final class FileSizePreProcessor implements PreProcessor
{
    protected $filesize;
    
    public function __construct($filesize)
    {
        $this->filesize = $filesize;
    }
    /**
     * @inheritDoc
     */
    public function process(FileStream $stream, Metadata $metadata)
    {
        $size = $stream->getSize();
        if ($this->filesize < $size) {
            return new ProcessingStatus(ProcessingStatus::REJECTED, 'File too large.');
        }
        return new ProcessingStatus(ProcessingStatus::OK, 'File smaller than maximum file size.');
    }
}
