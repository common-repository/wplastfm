<?php
/*
Plugin Name: WPLastfm
Plugin URI: http://www.kosmonauten.cc/wordpress/wplastfm
Description: Displays recent tracks from your last.fm account.
Author: Christian Klein
Version: 1.1.1
Author URI: http://www.kosmonauten.cc/
*/
    
  if (!defined('WPLASTFM_VERSION'))
    define('WPLASTFM_VERSION', '1.1.1');
  if (!defined('WPLASTFM_DIR'))
    define('WPLASTFM_DIR', WP_PLUGIN_DIR.'/'.basename(dirname(__FILE__)).'/cache');
    
  $wplastfm_options = array();
  require_once('wplastfm-settings.php');
  
  load_plugin_textdomain('wplastfm', false, dirname(plugin_basename(__FILE__)) . '/lang');
  add_action('wp_head', 'wplastfm_css');
  
  if (!file_exists(WPLASTFM_DIR))
    mkdir(WPLASTFM_DIR, 0777);
  
  /**
   * Includes the wplastfm.css, if exists.
   */
  function wplastfm_css() {
    $relpath = '/'.dirname(plugin_basename(__FILE__)).'/wplastfm.css';
    if (file_exists(WP_PLUGIN_DIR.$relpath)) {
      echo '<link rel="stylesheet" type="text/css" href="'.WP_PLUGIN_URL.$relpath.'" />'."\n";
    }
  }
    
  /**
   * 
   * @param String
   * @param Int
   * @param Array
   */
  function wplastfm($user, $n=5, $o=array()) {
    global $wplastfm_options;
    $options = array_merge($wplastfm_options, $o);
    $options['list'] = 'ul';
    $options['before'] = '<li>';
    $options['after'] = '</li>';
    
    echo "\n".'<'.$options['list'].' class="lastfm">'."\n";
    
    if (!function_exists('simplexml_load_file')) {
      echo $options['before'].__('The plugin requires PHP 5!', 'wplastfm').$options['after']."\n";
      echo "\n</".$options['list'].">\n";
      return;
    }
    
    $src = wplastfm_source($user);
    
    if ($n <= 10)
      $src .= '?limit='.$n;
    
    if ($options['cache'] >  0) {
      $file = WPLASTFM_DIR."/recenttracks_".$user.".xml";
      $success = wplastfm_update($file, $src);
      
      $xml = @simplexml_load_file($file);
    }
    else {
      $data = wplastfm_get_recenttracks($src);
      if (!empty($data))
        $success = true;

      $xml = @simplexml_load_string($data);
    }
    
    $imagesizes = array('small' => 0, 'medium' => 1, 'large' => 2);
    $imageheight = array('small' => 32, 'medium' => 64, 'large' => 126);
    
    $options['imagesize'] = strtolower($options['imagesize']);
    if (in_array($options['imagesize'], array('small', 'medium', 'large')))
      $imagesize = $imagesizes[$options['imagesize']];
    else
      $imagesize = 'small';
    
    if (@array_key_exists('track', @get_object_vars($xml))) {
      $max = count($xml->track);
      if ($max > 0) {
        if ($max > $n) $max = $n;
        
        for ($i=0;$i < $max;$i++) {
          if (!$success && ($xml->track[$i]['nowplaying'] == "true")) { $i++; $max++; }
          $track = array();
          $track['artist'] = $xml->track[$i]->artist;
          $track['title'] = $xml->track[$i]->name;
          $track['album'] = $xml->track[$i]->album;
          $track['img'] = $xml->track[$i]->image[$imagesize];
          $track['url'] = $xml->track[$i]->url;
          $track['date'] = (int) $xml->track[$i]->date['uts'];
          $track['nowplaying'] = $xml->track[$i]['nowplaying'];
          
          if ($track['img'] == "" && $options['useartistimage'])
            $track['img'] = wplastfm_get_artistimage($track['artist']);
          
          if ($track['nowplaying'] == "true" && $success) {
            $track['time'] = __('Listening now','wplastfm');
          }
          else {
            if (time()-$track['date'] > 86400) {
              if (!empty($options['timeformat'])) $track['time'] = date($options['timeformat'], $track['date']);
            }
            else
              $track['time'] = sprintf(__('%s ago', 'wplastfm'), human_time_diff($track['date']));
          }   
          
          $track['url'] = str_replace('www.last.fm',__('www.last.fm','wplastfm'),$track['url']);
          $track['artist_url'] = substr($track['url'], 0, strpos($track['url'], "/_/"));
          
          $track['track_long'] = $track['artist'].' - '.$track['title'];
          $track['track'] = wplastfm_trunc($track['track_long'], $options['trunctrack']);

          $track['album_long'] = $track['album'];
          $track['album'] = wplastfm_trunc($track['album'], $options['truncalbum']);

          $track['artist_long'] = $track['artist'];
          $track['artist'] = wplastfm_trunc($track['artist'], $options['truncartist']);

          $track['title_long'] = $track['title'];
          $track['title'] = wplastfm_trunc($track['title'], $options['trunctitle']);
          
          $track = wplastfm_specialchars($track);
          
          $tags = array();
          $tags['track'] = '<a href="'.$track['url'].'" title="'.$track['time'];
          if ($track['track_long'] != $track['track']) $tags['track'] .=  ' | '.$track['track_long'];
          $tags['track'] .= '">'.$track['track'].'</a>';
          
          $tags['artist'] = '<a href="'.$track['artist_url'].'"';
          if ($track['artist_long'] != $track['artist']) $tags['artist'] .= ' title="'.$track['artist_long'].'"';
          $tags['artist'] .= '>'.$track['artist'].'</a>';
          
          $tags['title'] = '<a href="'.$track['url'].'"';
          if ($track['title_long'] != $track['title']) $tags['title'] .= ' title="'.$track['title_long'].'"';
          $tags['title'] .= '>'.$track['title'].'</a>';
          
          if (!empty($track['album'])) {
            $tags['album'] = '<span class="lastfm-album"';
            if ($track['album_long'] != $track['album']) $tags['album'] .= ' title="'.$track['album_long'].'"';
            $tags['album'] .= '>'.$track['album'].'</span>';
          }
          
          if(!empty($track['time'])) $tags['time'] = '<span class="lastfm-time">'.$track['time'].'</span>';
          if ($track['img'] != "") { $tags['img'] = '<img src="'.$track['img'].'" alt="" title="'.$track['album_long'].'"'; $tags['img'] .= ($options['albumcover_height'] > 0) ? ' height="'.$options['albumcover_height'].'"' : ' height="'.$imageheight[$options['imagesize']].'"'; $tags['img'] .= ' />'; }
          else { $tags['img'] = ''; }
          
          $template = $options['template'];
          $template = str_replace("%track%", $tags['track'], $template);
          $template = str_replace("%artist%", $tags['artist'], $template);
          $template = str_replace("%title%", $tags['title'], $template);
          $template = str_replace("%album%", $tags['album'], $template);
          $template = str_replace("%time%", $tags['time'], $template);
          $template = str_replace("%img%", $tags['img'], $template);
          
          echo $options['before'].$template.$options['after']."\n";
        }
      }
      else {
        echo $options['before'].__('No data available.', 'wplastfm').$options['after']."\n";;
      }
    }
    else {
      echo $options['before'].__('No data available.', 'wplastfm').$options['after']."\n";;
    }
    echo "\n</".$options['list'].">\n";
  }

  /**
   * 
   * @param String    Last.fm username
   * @return String   Source URL
   */
  function wplastfm_source($user) {
    return "http://ws.audioscrobbler.com/2.0/user/".$user."/recenttracks.xml";
  }
  
  /**
   * 
   * @param string  The source to retrieve the data
   * @return string XML data if exists, else empty string
   */
  function wplastfm_get_recenttracks($src) {
    add_filter('http_headers_useragent', 'wplastfm_set_useragent');

    $response = wp_remote_get($src, array( 'method' => 'GET', 'timeout' => 3, 'redirection' => 5, 'httpversion' => 1.0, 'blocking' => true));

    if (is_wp_error($response)) {
      echo "<!-- WPLastfm Error: ".$response->get_error_message()." //-->\n";
      return '';
    }
      
    if (!empty($response[body]) && $response[response][code]=='200')
      return $response[body];
    
    return '';
  }
  
  /**
   * Retrieves the data from the Last.fm-API and stores it locally
   * 
   * @param string  The file name of the local xml file
   * @param string  The source to retrieve the data
   * @return bool   True if the data was retrieved successfully, otherwise false.
   */
  function wplastfm_update($file, $src) {
    global $wplastfm_options;
      
    if (time()-$wplastfm_options['cache'] > @filemtime($file)) {
      $data = wplastfm_get_recenttracks($src);
      if (!empty($data)) {
        $handle = fopen($file, "w");
        fwrite($handle, $data);
      }
      else {
        return false;
      }
    }
    return true;
  }
  
  /**
   * 
   * @param String
   */
  function wplastfm_get_artistimage($artist) {
    global $wp_version;
    if (version_compare($wp_version, '2.9', '<'))
      return '';
    
    $src = "http://ws.audioscrobbler.com/2.0/artist/".urlencode($artist)."/images.xml?limit=1&autocorrect=1";

    add_filter('http_headers_useragent', 'wplastfm_set_useragent');
    //apply_filters( 'http_request_timeout', 1);
    $response = wp_remote_get($src, array( 'method' => 'GET', 'timeout' => 2, 'redirection' => 5, 'httpversion' => 1.0, 'blocking' => true));

    if (is_wp_error($response)) {
      echo "<!-- WPLastfm Error: ".$response->get_error_message()." //-->\n";
      return '';
    }

    if (!empty($response[body]) && $response[response][code]=='200') {
      $xml = @simplexml_load_string($response[body]);
      if (@array_key_exists('image', @get_object_vars($xml))) {
        return $xml->image[0]->sizes->size[2];
      }
    }
    else {
      return '';
    }
  }
  
  /**
   * Sets the Useragent sent by the HTTP API to Wordpress/<version>;Lastfm/<version>
   * 
   * @return string   The Useragent including the WPLastfm-Version.
   */
  function wplastfm_set_useragent($s) {
      return $s.' WPLastfm/'.WPLASTFM_VERSION;
  }
  
  /**
   * 
   * @param String
   * @param Int
   * @return String
   */
  function wplastfm_trunc($str, $n) {
    if ($n == 0)
      return $str;
    if (mb_strlen($str, get_option('blog_charset')) > $n)
      return trim(mb_substr($str, 0, $n-3, get_option('blog_charset'))).'...';
    else
      return $str;
  }

  /**
   *
   * @param Array
   * @return Array
   */
  function wplastfm_specialchars($arr) {
    foreach ($arr as $key => $value) {
      $arr[$key] = wp_specialchars($value);
    }
    return $arr;
  }
  
  
if (version_compare($wp_version, '2.8', '>=')) { 
  class wplastfm_Widget extends WP_Widget {
    function wplastfm_Widget() {
      $widget_ops = array('classname' => 'widget_wplastfm', 'description' => __('Displays recent tracks from your last.fm account.', 'wplastfm') );
      $control_ops = array( 'width' => 300);

      $this->WP_Widget('wplastfm', 'Last.fm recent tracks', $widget_ops, $control_ops);
    }
   
    function widget($args, $instance) {
      extract($args, EXTR_SKIP);
      if (function_exists('simplexml_load_file')) {
        echo $before_widget;
        $title = empty($instance['title']) ? '' : $instance['title'];
        $user_name = empty($instance['user_name']) ? 'rj' : $instance['user_name'];
        $track_numbers = empty($instance['track_numbers']) ? '5' : $instance['track_numbers'];

        if (!empty($instance['lastfm_template'])) 
          $options['template'] = $instance['lastfm_template'];
        
        if (isset($instance['trunctrack'])) $options['trunctrack'] = $instance['trunctrack'];
        if (isset($instance['truncalbum'])) $options['truncalbum'] = $instance['truncalbum'];
        if (isset($instance['truncartist'])) $options['truncartist'] = $instance['truncartist'];
        if (isset($instance['trunctitle'])) $options['trunctitle'] = $instance['trunctitle'];
        if (isset($instance['imagesize'])) $options['imagesize'] = $instance['imagesize'];
        if (isset($instance['albumcover_height'])) $options['albumcover_height'] = $instance['albumcover_height'];
        if (isset($instance['profilelink'])) $options['profilelink'] = $instance['profilelink'];

        if (!empty($instance['timeformat'])) $options['timeformat'] = $instance['timeformat'];
        
        if (!empty($title)) {
          echo $before_title;
          if ($options['profilelink'] == true) echo '<a href="http://'.__('www.last.fm','wplastfm').'/user/'.$user_name.'">'.$title.'</a>';
          else echo $title;
          echo $after_title; 
        }
        echo "\n<!-- Last.fm Recent Tracks //-->\n";
        echo wplastfm($user_name, $track_numbers, $options);
        echo $after_widget;
      }
    }
   
    function update($new_instance, $old_instance) {
      $instance = $old_instance;
      $instance['title'] = strip_tags($new_instance['title']);
      $instance['user_name'] = strip_tags($new_instance['user_name']);
      if (is_numeric($new_instance['track_numbers'])) {
        if ($new_instance['track_numbers'] <= 10)
          $instance['track_numbers'] = strip_tags($new_instance['track_numbers']);
        else
          $instance['track_numbers'] = 10;
      }
      $instance['lastfm_template'] = html_entity_decode($new_instance['lastfm_template']);
      
      if (is_numeric($new_instance['trunctrack'])) $instance['trunctrack'] = strip_tags($new_instance['trunctrack']);
      if (is_numeric($new_instance['truncalbum'])) $instance['truncalbum'] = strip_tags($new_instance['truncalbum']);
      if (is_numeric($new_instance['truncartist'])) $instance['truncartist'] = strip_tags($new_instance['truncartist']);
      if (is_numeric($new_instance['trunctitle'])) $instance['trunctitle'] = strip_tags($new_instance['trunctitle']);
      
      if ($new_instance['profilelink'] == 'true')
        $instance['profilelink'] = true;
      else
        $instance['profilelink'] = false;
      $instance['timeformat'] = strip_tags($new_instance['timeformat']);
      $instance['imagesize'] = strip_tags($new_instance['imagesize']);
      if (is_numeric($new_instance['albumcover_height'])) $instance['albumcover_height'] = strip_tags($new_instance['albumcover_height']);
      
      $src = wplastfm_source($instance['user_name']);
      $file = WPLASTFM_DIR."/recenttracks_".$instance['user_name'].".xml";
      wplastfm_update($file, $src);

      return $instance;
    }
   
    function form($instance) {
      global $wplastfm_options;
      $instance = wp_parse_args( (array) $instance, array( 'title' => 'Last.fm', 'user_name' => '', 'track_numbers' => '5', 'lastfm_template' => $wplastfm_options['template'], 'trunctrack' => $wplastfm_options['trunctrack'], 'truncalbum' => $wplastfm_options['truncalbum'], 'truncartist' => $wplastfm_options['truncartist'], 'trunctitle' => $wplastfm_options['trunctitle'], 'timeformat' => $wplastfm_options['timeformat'], 'imagesize' => $wplastfm_options['imagesize'], 'albumcover_height' => $wplastfm_options['albumcover_height'], 'profilelink' => $wplastfm_options['profilelink']) );
      
      $title = esc_attr($instance['title']);
      $user_name = esc_attr($instance['user_name']);
      $track_numbers = esc_attr($instance['track_numbers']);
      $lastfm_template = htmlentities($instance['lastfm_template']);
      $trunc_track = esc_attr($instance['trunctrack']);
      $trunc_album = esc_attr($instance['truncalbum']);
      $trunc_artist = esc_attr($instance['truncartist']);
      $trunc_title = esc_attr($instance['trunctitle']);
      $timeformat = esc_attr($instance['timeformat']);
      $imagesize = esc_attr($instance['imagesize']);
      $imageheight = esc_attr($instance['albumcover_height']);
      $profilelink = $instance['profilelink'];
      
      if (!function_exists('simplexml_load_file')) {
        echo '<h3>'.__('The plugin requires PHP 5!', 'wplastfm').'</h3>';
        $disabled = ' disabled="disabled"';
      }
      ?>
        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"<?php echo $disabled; ?> />
          
          <small><input type="checkbox" id="<?php echo $this->get_field_id('profilelink'); ?>" name="<?php echo $this->get_field_name('profilelink'); ?>" type="text" value="true" <?php if ($profilelink) echo 'checked="checked"'; ?><?php echo $disabled; ?> /> <label for="<?php echo $this->get_field_id('profilelink'); ?>"><?php _e('Link to profile', 'wplastfm'); ?></label></small>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('user_name'); ?>"><?php _e('Username'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('user_name'); ?>" name="<?php echo $this->get_field_name('user_name'); ?>" type="text" value="<?php echo $user_name; ?>"<?php echo $disabled; ?> />
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('track_numbers'); ?>"><?php _e('Number of tracks', 'wplastfm'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('track_numbers'); ?>" name="<?php echo $this->get_field_name('track_numbers'); ?>" type="text" value="<?php echo $track_numbers; ?>"<?php echo $disabled; ?> />
          <small><?php _e('(maximal 10)','wplastfm'); ?></small>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('lastfm_template'); ?>"><?php _e('Template'); ?></label>
          <textarea class="widefat" rows="3" cols="16" id="<?php echo $this->get_field_id('lastfm_template'); ?>" name="<?php echo $this->get_field_name('lastfm_template'); ?>"<?php echo $disabled; ?>><?php echo $lastfm_template; ?></textarea>
          <small><?php _e('Tags: %track% (= %artist% - %title%), %artist%, %title%, %album%, %time%, %img%', 'wplastfm'); ?></small>
        </p>
        <h3><?php _e('Maximum length', 'wplastfm'); ?></h3>
        <p>
          <label for="<?php echo $this->get_field_id('trunctrack'); ?>"><?php _e('Track:', 'wplastfm'); ?></label> <input id="<?php echo $this->get_field_id('trunctrack'); ?>" name="<?php echo $this->get_field_name('trunctrack'); ?>" type="text" value="<?php echo $trunc_track; ?>" style="width: 30px;" <?php echo $disabled; ?> />
          <label for="<?php echo $this->get_field_id('truncalbum'); ?>"><?php _e('Album:', 'wplastfm'); ?></label> <input id="<?php echo $this->get_field_id('truncalbum'); ?>" name="<?php echo $this->get_field_name('truncalbum'); ?>" type="text" value="<?php echo $trunc_album; ?>" style="width: 30px;" <?php echo $disabled; ?> /><br />
          
          <label for="<?php echo $this->get_field_id('truncartist'); ?>"><?php _e('Artist:', 'wplastfm'); ?></label> <input id="<?php echo $this->get_field_id('truncartist'); ?>" name="<?php echo $this->get_field_name('truncartist'); ?>" type="text" value="<?php echo $trunc_artist; ?>" style="width: 30px;" <?php echo $disabled; ?> />
          <label for="<?php echo $this->get_field_id('trunctitle'); ?>"><?php _e('Title:', 'wplastfm'); ?></label> <input id="<?php echo $this->get_field_id('trunctitle'); ?>" name="<?php echo $this->get_field_name('trunctitle'); ?>" type="text" value="<?php echo $trunc_title; ?>" style="width: 30px;" <?php echo $disabled; ?> /><br />
          <small><?php _e('0 = no truncation', 'wplastfm'); ?></small>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('imagesize'); ?>"><?php _e('Image size:', 'wplastfm'); ?></label>
          <select id="<?php echo $this->get_field_id('imagesize'); ?>" name="<?php echo $this->get_field_name('imagesize'); ?>"<?php echo $disabled; ?>>
            <option value="small" <?php if ($imagesize == 'small') echo ' selected="selected" '; ?>><?php _e('Small (34x34)', 'wplastfm'); ?></option>
            <option value="medium" <?php if ($imagesize == 'medium') echo ' selected="selected" '; ?>><?php _e('Medium (64x64)', 'wplastfm'); ?></option>
            <option value="large" <?php if ($imagesize == 'large') echo ' selected="selected" '; ?>><?php _e('Large (126x126)', 'wplastfm'); ?></option>
          </select>
          <br />
          <label for="<?php echo $this->get_field_id('albumcover_height'); ?>"><?php _e('Image height:', 'wplastfm'); ?></label> <input id="<?php echo $this->get_field_id('albumcover_height'); ?>" name="<?php echo $this->get_field_name('albumcover_height'); ?>" type="text" value="<?php echo $imageheight; ?>" style="width: 28px;" <?php echo $disabled; ?> /> <?php _e('pixel', 'wplastfm'); ?>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('timeformat'); ?>"><?php _e('Time format (over 24h):', 'wplastfm'); ?></label>
          <select id="<?php echo $this->get_field_id('timeformat'); ?>" name="<?php echo $this->get_field_name('timeformat'); ?>"<?php echo $disabled; ?>>
            <option value="d.m, H:i" <?php if ($timeformat == 'd.m, H:i') echo ' selected="selected" '; ?>><?php echo date('d.m, H:i'); ?></option>
            <option value="Y-m-d H:i" <?php if ($timeformat == 'Y-m-d H:i') echo ' selected="selected" '; ?>><?php echo date('Y-m-d H:i'); ?></option>
            <option value="j M h:ia" <?php if ($timeformat == 'j M h:ia') echo ' selected="selected" '; ?>><?php echo date('j M h:ia'); ?></option>
            <option value="" <?php if (empty($timeformat)) echo ' selected="selected" '; ?>><?php _e('Use self-defined', 'wplastfm');?></option>
          </select>
        </p>
      <?php
    }
  }
  add_action('widgets_init', create_function('', 'return register_widget("wplastfm_Widget");'));
}
?>
