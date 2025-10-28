<?php

namespace BV\Shortcodes\Classes;

if (!defined('ABSPATH'))
    exit;
class ResourcesParams
{
    public function __construct(
        public string $search = '',
        public string $searchTitle = '',
        public string $type = '',
        public int $limit = 12,
        public int $max = 50,
        public bool $show_pagination = false,
        public int $page = 1,
        public bool $showfilters = true,
    ) {
    }
}
