<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_status_badge')) {
    function get_status_badge($status) {
        switch ($status) {
            case 'draft': return 'secondary';
            case 'submitted': return 'warning';
            case 'approved': return 'success';
            case 'rejected': return 'danger';
            default: return 'secondary';
        }
    }
}
