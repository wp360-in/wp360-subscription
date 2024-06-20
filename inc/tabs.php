<?php

function Wp360_Subscription_tabs(){
    $items = [
        [
            'label'=>'All Subscription',
            'page'=>'wp360_subscription'
        ],
        [
            'label'=>'Email configuration',
            'page'=>'wp360-subscription-email-config'
        ],
        [
            'label'=>'General Settings',
            'page'=>'wp360-subscription-general-settings'
        ],
        [
            'label'=>'License',
            'page'=>'wp360-subscription-license-key'
        ],
    ];
    $res = '<div class="wp360_admin_tabs"><ul>';
    foreach($items as $item){
        $active = '';
        if(isset($_GET['page']) && $_GET['page'] == $item['page']){
            $active = 'class="active"';
        } 
        $res .= '
        <li>
            <a href="'. admin_url('admin.php?page=').$item['page'].'" '.$active.'>'.$item['label'].'</a>
        </li>';
    }
    $res .= '</ul></div>
    ';
    return $res;
}