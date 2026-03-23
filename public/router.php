<?php

if (preg_match('/\.(?:jpg|jpeg|png|gif|ico|css|js|svg|webp|ttf|woff|woff2|pdf|mp4|webm|mov)$/', $_SERVER['REQUEST_URI'])) {
    return false;
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}

require 'index.php';
