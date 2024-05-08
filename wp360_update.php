<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!session_id()){
    session_start();
}

add_action('admin_enqueue_scripts', 'wp360subscription_UpdateScripts');
function wp360subscription_UpdateScripts() {    
    wp_enqueue_script(WP360_SUBSCRIPTION_SLUG.'_update_js', plugin_dir_url(__FILE__).'admin/assets/js/wp360_update_script.js?v='.time().'', array('jquery'), WP360SUBSCRIPTIONVER, true);
    $wp360_subscriptions_plugin_basename    = dirname(plugin_basename(__FILE__)); 
    $wp360_subscriptions_localization_data  = array(
        'wp360_subscriptions_ajax_url'          => admin_url('admin-ajax.php'),
        'wp360_subscriptions_plugin_slug' =>$wp360_subscriptions_plugin_basename, // Add more data as needed
    );
    wp_localize_script(WP360_SUBSCRIPTION_SLUG.'_update_js', 'wp360_subscriptions_admin_data', $wp360_subscriptions_localization_data);
}


add_action('wp_head', 'get_release_date_subscription');
function get_release_date_subscription() {
    if(isset($_SESSION['wp360_release_data_subscription'])){
        $data = $_SESSION['wp360_release_data_subscription'];
        return $data;
    }
}
add_action('admin_init', function() {

    $autoload_file = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    if (!class_exists('GuzzleHttp\Client') && file_exists($autoload_file)) {
        require_once $autoload_file;
    }

 //   require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
    if (!isset($_SESSION['wp360_release_data_subscription'])) {
        $client = new GuzzleHttp\Client();
        try {
            $repoOwner      = 'wp360-in';
            $repoName       = 'wp360-subscription';
            $response       = $client->request('GET', "https://api.github.com/repos/{$repoOwner}/{$repoName}/releases/latest");
            $releaseData    = json_decode($response->getBody(), true);
            $_SESSION['wp360_release_data_subscription'] = $releaseData;
        } catch (Exception $e) {
            error_log('WP360 Invoice Error ' .$e->getMessage());
        }
    } else {
        $responseGit        = $_SESSION['wp360_release_data_subscription'];
        $release_version    = $responseGit['tag_name'];
    }
    if (!empty($release_version) && version_compare(wp360_subscriptions_plugin_version(), $release_version, '<')) {
        error_log('Greater than current version SUBSCRIPTION');
        update_option('wp360_plugin_available_version_subscription', $release_version);
    }
  
});

add_action('after_plugin_row', 'custom_plugin_update_notice_subscriptions', 10, 2);
function custom_plugin_update_notice_subscriptions($plugin_file, $plugin_data) {
    if (  isset( $plugin_data['plugin'] ) && $plugin_data['plugin'] === $plugin_file &&  $plugin_data['Name'] == "Wp360 SUBSCRIPTION") {
        $aviliable_version = get_option('wp360_plugin_available_version_subscription');
        if (wp360_subscriptions_plugin_version() !=  $aviliable_version) {
            ?>
            <tr class="plugin-update-tr active wp360_alert_message wp360_success_subscriptions_update" id="" data-title="Wp360 Invoice">
                <td class="plugin-update colspanchange" colspan="4">
                    <div class="update-message inline notice notice-warning notice-alt"> 
                        <p>
                            <?php
                                printf(
                                    __('There is a new version of %s available. <a href="#" class="%s" data-slug="%s">View version %s details</a> or <a href="javascript:void(0)" class="%s"> Update now.</a>', 'wp360-invoice'),
                                    'WP360 Invoice', // Plugin name
                                    'wp360-subscriptions-view-details thickbox open-plugin-details-modal', // View details link class
                                    urlencode($plugin_file), // Plugin file
                                    esc_html($aviliable_version), // Available version
                                    'wp360-subscriptions-update-click' // Update now link class
                                );
                            ?>
                        </p>
                   </div>
                </td>
            </tr> 
            <?php
        }
    }
}

add_action('wp_ajax_update_wp360_subscription', 'update_wp360_subscription_callback');
function update_wp360_subscription_callback() {
    if(isset($_POST['action']) &&  $_POST['action'] == "update_wp360_subscription"){
        $aviliable_version = get_option('wp360_plugin_available_version_subscription');
        $plugin_dir     = plugin_dir_path(__FILE__);


        $autoload_file = plugin_dir_path(__FILE__) . 'vendor/autoload.php';
        if (!class_exists('GuzzleHttp\Client') && file_exists($autoload_file)) {
            require_once $autoload_file;
        }
       // require_once $plugin_dir . 'vendor/autoload.php';

        $repoOwner      = 'wp360-in';
        $repoName       = 'wp360-subscription';
        $branch         = 'main'; 
        $token          = '';
       // $license_file_path      = plugin_dir_path( __FILE__ ) . 'token.txt';
       // $token                  = file_get_contents( $license_file_path );
        //$token                  = trim( $token );
        $apiUrl         = "https://api.github.com/repos/{$repoOwner}/{$repoName}/contents";
        $clonePath      = plugin_dir_path(__FILE__);
        // Initialize GuzzleHttp client
        $client = new GuzzleHttp\Client();
        wp360_subscriptions_fetchFilesFromDirectory($client, $apiUrl, $clonePath, $token);
        $result = array(
            'success' => true,
            'aviliableVersion'=>$aviliable_version,
            'message' => 'Plugin updated successfully!'
        );
        return wp_send_json_success($result);
        wp_die();
    }
}

function wp360_subscriptions_fetchFilesFromDirectory($client, $apiUrl, $localDirectory, $token) {
    $headers = [
        'Accept' => 'application/vnd.github.v3+json',
    ];
    $response = $client->request('GET', $apiUrl, [
        'headers' => $headers,
    ]);
    $files = json_decode($response->getBody(), true);
    foreach ($files as $file) {
        if ($file['type'] === 'file') {
            $fileContent = file_get_contents($file['download_url']);
            $localFilePath = $localDirectory . '/' . $file['name'];
            if (file_exists($localFilePath)) {
                file_put_contents($localFilePath, $fileContent);
                //error_log(' File '.$file['name'].' updated locally 1. <br>');
            } else {
                file_put_contents($localFilePath, $fileContent);
               // error_log('File '.$file['name'].' saved locally 1 <br>');
            }
        } elseif ($file['type'] === 'dir') {
            $subDirectoryUrl = $file['url'];
            $subDirectoryName = $file['name'];
            $subLocalDirectory = $localDirectory . '/' . $subDirectoryName;
            if (!file_exists($subLocalDirectory)) {
                mkdir($subLocalDirectory, 0777, true);
            }
            wp360_subscriptions_fetchFilesFromDirectory($client, $subDirectoryUrl, $subLocalDirectory, $token);
        }
    }
}
// add_action('admin_init', 'clear_plugin_updates');
// function clear_plugin_updates() {
//     delete_site_transient('update_plugins');
// }

add_filter( 'site_transient_update_plugins', 'wp360_subscriptions_push_update' );
function wp360_subscriptions_push_update( $transient ){
    if ( empty( $transient->checked ) ) {
        return $transient;
    }
    $plugin_basename    = plugin_basename(__FILE__); // Get the plugin's basename
    $custom_plugin_file = dirname($plugin_basename) . '/index.php'; // Combine with '/wp360-invoice.php'
    $available_version  = get_option('wp360_plugin_available_version_subscription');
    $installed_version  = get_plugin_data(WP_PLUGIN_DIR . '/' . $custom_plugin_file)['Version'];
    if (!empty($available_version) && version_compare($available_version, $installed_version, '>')) {
        $res = new stdClass();
        $res->slug = dirname($plugin_basename); // Extract the directory name as the slug
        $res->plugin = $custom_plugin_file;
        $res->new_version = $available_version;
        $res->tested = '3.4.2';
        $plugin_slug    = basename(dirname(__FILE__));
        $transient->response[$res->plugin] = $res;
    }
    if (isset($transient->response['wp360-subscription'])) {
        $plugin_data = $transient->response['wp360-subscription'];
        unset($plugin_data->package);
    }
    return $transient;
}
//view details modal
add_filter( 'plugins_api', 'wp360_subscriptions_plugin_info', 20, 3);
function wp360_subscriptions_plugin_info( $res, $action, $args ){
    if( 'plugin_information' !== $action ) {
        return $res;
    }
    if( plugin_basename( __DIR__ ) !== $args->slug ) {
            return $res;
    }
    $plugin_data         =  get_plugin_data(plugin_dir_path(__FILE__) . 'index.php');
    $releaseData         =  get_release_date_subscription();
    $name                =  $plugin_data['Name'];
    $textDomain          =  $plugin_data['TextDomain'];
    $author              =  $plugin_data['Author'];
    $testedupto          =  $plugin_data['WC tested up to'];
    $requiresWP          =  $plugin_data['RequiresWP'];
    $requiresatleast_Php =  $plugin_data['RequiresPHP'];
    $releaseVersion      =  $releaseData['tag_name'];
    $created_at          =  $releaseData['created_at'];
    $tarball_url         =  $releaseData['tarball_url'];
    $zipball_url         =  $releaseData['zipball_url'];
    $bodymessage         =  $releaseData['body'];
    $created_date        = new DateTime($created_at);
    $current_date        = new DateTime();
    $interval = $current_date->diff($created_date);
    $lastupdateddate = '';
    if ($interval->y > 0) {
        $lastupdateddate = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
    } elseif ($interval->m > 0) {
        $lastupdateddate = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
    } elseif ($interval->d > 0) {
        $lastupdateddate = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
    } elseif ($interval->h > 0) {
        $lastupdateddate = $interval->h . ' hr' . ($interval->h > 1 ? 's' : '') . ' ago';
    } elseif ($interval->i > 0) {
        $lastupdateddate = $interval->i . ' min' . ($interval->i > 1 ? 's' : '') . ' ago';
    } else {
        $lastupdateddate = 'Just now';
    }
    $res = new stdClass();
    $res->name   = $name;
    $res->slug   = $textDomain;
    $res->author = $author;
    $res->author_profile = 'author_profile';
    $res->version       = $releaseVersion;
    $res->tested        =  $testedupto;
    $res->requires      = $requiresWP;
    $res->requires_php  = $requiresatleast_Php;
    $res->download_link = $tarball_url;
    $res->trunk         = $zipball_url;
    $res->last_updated  = $lastupdateddate;
    $htmlChangelog = '<div>
        <h5>'.$releaseVersion.'</h5>
        <ul>
            <li>'.$bodymessage.'</li>
        </ul>
        <a href="https://raw.githubusercontent.com/wp360-in/wp360-subscription/main/changelog.txt">
        '.__('See changelog for all versions.','wp360-subscription').'</a>
    </div>';
    $installation = '<div>
        <h5>== Installation ==</h5>
        <ul>
            <li>1. Upload the `wp360-invoice` directory to the `/wp-content/plugins/` directory.</li>
            <li>2. Activate the plugin through the "Plugins" menu in WordPress.</li>
            <li>3. After activation, navigate to Settings -> Permalinks and click on "Save Changes" to regenerate rewrite rules.</li>
            <li>4. Configure the plugin settings via the "wp360 Invoice" menu in the WordPress admin.</li>
        </ul>
    </div>';
    $res->sections = array(
        'description' => $bodymessage,
        'installation' => $installation,
        'changelog' => $htmlChangelog,
    );
    $res->banners = array(
        'low' => 'https://raw.githubusercontent.com/wp360-in/wp360-subscription/main/screenshots/subscriptions_history.jpg',
        'high' => 'https://raw.githubusercontent.com/wp360-in/wp360-subscription/main/screenshots/subscriptions_history.jpg'
    );
    unset($res->download_link);
    return $res;
}
add_action('admin_footer',function(){
echo '<style>
.plugin_install_from_iframe[data-slug="wp360-subscription"]{
    display: none!important;
}
</style>';
});