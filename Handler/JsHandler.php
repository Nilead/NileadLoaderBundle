<?php

namespace Nilead\LoaderBundle\Handler;

class JsHandler extends Handler{
    protected $filePattern = "<script type=\"text/javascript\" src=\"%s\"></script>\n";

    protected $extension = 'js';
}