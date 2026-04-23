<?php

function paginate($total_items, $per_page = 10, $current_page = 1) {
    $current_page = max(1, (int)$current_page);
    $per_page = max(1, (int)$per_page);
    $total_items = max(0, (int)$total_items);
    
    $total_pages = $total_items > 0 ? ceil($total_items / $per_page) : 1;
    $current_page = min($current_page, $total_pages);
    
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'current_page' => $current_page,
        'per_page' => $per_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1
    ];
}

function pagination_links($base_url, $pagination, $params = []) {
    $html = '<nav><ul class="pagination justify-content-center">';
    
    $all_params = array_merge($_GET, $params);
    unset($all_params['page']);
    
    $query_string = http_build_query($all_params);
    $url = $base_url . ($query_string ? '?' . $query_string . '&' : '?');
    
    $disabled = $pagination['current_page'] == 1 ? ' disabled' : '';
    $href = $pagination['has_prev'] ? $url . 'page=' . $pagination['prev_page'] : '#';
    
    $html .= '<li class="page-item' . $disabled . '">';
    $html .= '<a class="page-link" href="' . $href . '">Anterior</a></li>';
    
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $pagination['current_page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '">';
        $html .= '<a class="page-link" href="' . $url . 'page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
    }
    
    $disabled = $pagination['current_page'] == $pagination['total_pages'] ? ' disabled' : '';
    $href = $pagination['has_next'] ? $url . 'page=' . $pagination['next_page'] : '#';
    
    $html .= '<li class="page-item' . $disabled . '">';
    $html .= '<a class="page-link" href="' . $href . '">Siguiente</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}

function simple_pagination($base_url, $current_page, $total_pages) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav><ul class="pagination justify-content-center">';
    
    $query_string = http_build_query(array_merge($_GET, ['page' => 1]));
    $url = $base_url . '?' . $query_string;
    
    $prev_disabled = $current_page <= 1 ? ' disabled' : '';
    $prev_href = $current_page > 1 ? str_replace('page=1', 'page=' . ($current_page - 1), $url) : '#';
    
    $html .= '<li class="page-item' . $prev_disabled . '">';
    $html .= '<a class="page-link" href="' . $prev_href . '">&laquo;</a></li>';
    
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $page_url = str_replace('page=1', 'page=' . $i, $url);
        $html .= '<li class="page-item' . $active . '">';
        $html .= '<a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    $next_disabled = $current_page >= $total_pages ? ' disabled' : '';
    $next_href = $current_page < $total_pages ? str_replace('page=1', 'page=' . ($current_page + 1), $url) : '#';
    
    $html .= '<li class="page-item' . $next_disabled . '">';
    $html .= '<a class="page-link" href="' . $next_href . '">&raquo;</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}