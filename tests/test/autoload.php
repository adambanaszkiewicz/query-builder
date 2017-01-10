<?php

spl_autoload_register(function($class_name) {
    include __DIR__.'/../../src/'.str_replace(['\\', 'Requtize/'], ['/', ''], $class_name).'.php';
});
