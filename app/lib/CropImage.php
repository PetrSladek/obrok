<?php

namespace Myann;

use Nette\Http\FileUpload;
//use Nette\Environment;

class CropImage
{
    public $filename;
    public $x;
    public $y;
    public $w;
    public $h;
    public $sess_key;

    private $upload = null;
    
    public function __construct($value = null) {
        foreach (array('filename', 'sess_key', 'x', 'y', 'w', 'h') as $key) {
            if (isset($value[$key]) && is_scalar($value[$key]))
                $this->$key = $value[$key];
            else
                $this->$key = null;
        }
    }

    public function getFilename() {
        return $this->filename;
    }
    public function setFilename($filename) {
        $this->filename = $filename;
        return $this;
    }

    public function getCrop() {
        $result = array();
        foreach (array('x', 'y', 'w', 'h') as $key) {
            $result[$key] = $this->{$key};
        }
        return $result;
    }
    public function setCrop($crop) {
        foreach (array('x', 'y', 'w', 'h') as $key) {
            if (isset($crop[$key]) && is_scalar($crop[$key]))
                $this->$key = $crop[$key];
        }
        return $this;
    }



    public function hasUploadedFile() {
        return is_object($this->upload)
                && $this->upload instanceof FileUpload
                && $this->upload->isOk()
                && $this->upload->isImage();
    }
    public function setUploadedFile(FileUpload $file = null) {
        if($file === null)
            $this->sess_key = null;
        $this->upload = $file;
        return $this;
    }
    public function getUploadedFile() {
        return $this->upload;
    }
    
    
    public static function isFilled($control) {
        $val = $control->getValue();
        return !empty($val->filename);
    }



    /** @deprecated */
    public function toArray() {
        $result = array();
        foreach (array('filename', 'sess_key', 'x', 'y', 'w', 'h') as $key) {
            $result[$key] = $this->{$key};
        }
        return $result;
    }
    /** @deprecated */
    public function toArrayCrop() {
        return $this->getCrop();
    }

}
