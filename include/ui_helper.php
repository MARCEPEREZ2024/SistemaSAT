<?php

function ui_message($message, $type = 'info', $dismissible = true) {
    $types = [
        'success' => ['class' => 'success', 'icon' => 'bi-check-circle-fill'],
        'error' => ['class' => 'danger', 'icon' => 'bi-exclamation-circle-fill'],
        'warning' => ['class' => 'warning', 'icon' => 'bi-exclamation-triangle-fill'],
        'info' => ['class' => 'info', 'icon' => 'bi-info-circle-fill']
    ];
    
    $config = $types[$type] ?? $types['info'];
    $dismiss = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';
    
    return '<div class="alert alert-' . $config['class'] . ' alert-dismissible fade show" role="alert">
        <i class="bi ' . $config['icon'] . ' me-2"></i>' . $message . $dismiss . '
    </div>';
}

function ui_card($title, $content, $icon = '', $color = 'primary') {
    return '<div class="card border-left-' . $color . '">
        <div class="card-header bg-' . $color . ' bg-opacity-10">
            <h5 class="mb-0"><i class="bi ' . $icon . '"></i> ' . $title . '</h5>
        </div>
        <div class="card-body">' . $content . '</div>
    </div>';
}

function ui_badge($text, $color = 'secondary') {
    return '<span class="badge bg-' . $color . '">' . $text . '</span>';
}

function ui_button($text, $href, $icon = '', $color = 'primary', $size = '') {
    $size_class = $size ? 'btn-' . $size : '';
    return '<a href="' . $href . '" class="btn btn-' . $color . ' ' . $size_class . '">
        <i class="bi ' . $icon . '"></i> ' . $text . '
    </a>';
}

function ui_spinner($size = 'md', $text = 'Cargando...') {
    $sizes = [
        'sm' => 'spinner-border-sm',
        'md' => '',
        'lg' => 'spinner-border Style="width: 3rem; height: 3rem;"'
    ];
    $spinner = $sizes[$size] ?? $sizes['md'];
    
    return '<div class="d-flex justify-content-center align-items-center p-4">
        <div class="text-center">
            <div class="spinner-border ' . $spinner . ' text-primary" role="status"></div>
            <p class="mt-2 text-muted">' . $text . '</p>
        </div>
    </div>';
}

function ui_empty($icon, $title, $text, $button = '') {
    return '<div class="text-center p-5">
        <i class="bi ' . $icon . ' text-muted" style="font-size: 4rem;"></i>
        <h4 class="mt-3">' . $title . '</h4>
        <p class="text-muted">' . $text . '</p>
        ' . $button . '
    </div>';
}

function ui_table_responsive($headers, $rows, $empty_message = 'Sin datos') {
    if (empty($rows)) {
        return ui_empty('table', 'Sin datos', $empty_message);
    }
    
    $header_html = '<thead><tr>';
    foreach ($headers as $header) {
        $header_html .= '<th>' . $header . '</th>';
    }
    $header_html .= '</tr></thead>';
    
    $body_html = '<tbody>';
    foreach ($rows as $row) {
        $body_html .= '<tr>';
        foreach ($row as $cell) {
            $body_html .= '<td>' . $cell . '</td>';
        }
        $body_html .= '</tr>';
    }
    $body_html .= '</tbody>';
    
    return '<div class="table-responsive">
        <table class="table table-hover table-striped">' . $header_html . $body_html . '</table>
    </div>';
}

function ui_modal($id, $title, $body, $footer = '', $size = '') {
    $size_class = $size ? 'modal-' . $size : '';
    
    return '<div class="modal fade" id="' . $id . '" tabindex="-1">
        <div class="modal-dialog ' . $size_class . '">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">' . $title . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">' . $body . '</div>
                <div class="modal-footer">' . $footer . '</div>
            </div>
        </div>
    </div>';
}

function ui_progress($percent, $color = 'primary', $text = '') {
    return '<div class="progress" style="height: 20px;">
        <div class="progress-bar bg-' . $color . '" role="progressbar" style="width: ' . $percent . '%;">
            ' . ($text ?: $percent . '%') . '
        </div>
    </div>';
}

function ui_tabs($tabs, $active = 0) {
    $tabs_html = '<ul class="nav nav-tabs" role="tablist">';
    $content_html = '<div class="tab-content">';
    
    foreach ($tabs as $index => $tab) {
        $active_class = $index === $active ? 'active' : '';
        $tabs_html .= '<li class="nav-item">
            <button class="nav-link ' . $active_class . '" data-bs-toggle="tab" data-bs-target="#tab-' . $index . '">
                <i class="bi ' . $tab['icon'] . '"></i> ' . $tab['title'] . '
            </button>
        </li>';
        
        $content_html .= '<div class="tab-pane fade ' . ($index === $active ? 'show active' : '') . '" id="tab-' . $index . '">';
        $content_html .= $tab['content'] . '</div>';
    }
    
    $tabs_html .= '</ul>';
    $content_html .= '</div>';
    
    return $tabs_html . $content_html;
}

function ui_accordion($items, $multi = false) {
    $html = '<div class="accordion' . ($multi ? ' accordion-flush' : '') . '">';
    
    foreach ($items as $index => $item) {
        $show = $index === 0 ? 'show' : '';
        $collapsed = $index === 0 ? '' : 'collapsed';
        
        $html .= '<div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button ' . $collapsed . '" type="button" data-bs-toggle="collapse" data-bs-target="#accordion-' . $index . '">
                    ' . $item['title'] . '
                </button>
            </h2>
            <div id="accordion-' . $index . '" class="accordion-collapse collapse ' . $show . '">
                <div class="accordion-body">' . $item['content'] . '</div>
            </div>
        </div>';
    }
    
    $html .= '</div>';
    return $html;
}

function ui_breadcrumb($items) {
    $html = '<nav><ol class="breadcrumb">';
    
    foreach ($items as $index => $item) {
        $active = $index === count($items) - 1 ? 'active' : '';
        $href = isset($item['href']) ? $item['href'] : '#';
        
        $html .= '<li class="breadcrumb-item ' . $active . '">';
        if ($href !== '#' && !$active) {
            $html .= '<a href="' . $href . '">' . $item['text'] . '</a>';
        } else {
            $html .= $item['text'];
        }
        $html .= '</li>';
    }
    
    $html .= '</ol></nav>';
    return $html;
}