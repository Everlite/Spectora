<?php

// This file is purely for the IDE to stop complaining about missing classes.
// It is NOT used by the application at runtime.

namespace Barryvdh\DomPDF\Facade {
    class Pdf {
        public static function loadView($view, $data = [], $mergeData = [], $encoding = null) { return new static; }
        public static function setOptions(array $options) {}
        public function download($filename = 'document.pdf') {}
        public function stream($filename = 'document.pdf') {}
    }
}

namespace Symfony\Component\DomCrawler {
    class Crawler {
        public function __construct($node = null, $uri = null, $baseHref = null) {}
        public function filter($selector) { return new static; }
        public function count() { return 0; }
        public function text() { return ''; }
        public function attr($attribute) { return ''; }
        public function reduce($closure) { return new static; }
    }
}
