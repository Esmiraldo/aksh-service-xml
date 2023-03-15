<?php
/*
Plugin Name: Aksh XML - Pharmacies
Description: A custom plugin use [pharmacies_bzn] to your page
Author: BZN.GR
Version: 1.0.0
*/
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Access denied.' );
}
class Pharmacies_Shortcode
{
    private $url;
    private $xml;
    private $notdienste;
    public static function init() {
        add_shortcode('pharmacies_bzn', array(__CLASS__, 'get_pharmacies'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));
    }
    public static function enqueue_styles() {
        wp_enqueue_style('bzn-pharmacies-style', plugin_dir_url(__FILE__) . 'css/style.css');
    }
    public function __construct()
    {
        $this->url = 'https://www.aksh-service.de/notdienste/exporte/xml.php?m=koord&w=53.646758;7.610909&a=4;c=iso';
        $this->xml = simplexml_load_file($this->url);
        $this->notdienste = $this->xml->notdienste;
    }

    public function get_pharmacies()
    {
        $output = '<div class="bzn-pharm-style">';
        $output .= '<h3>' . $this->xml->beschreibung . '</h3>';
        $output .= '<p>' . $this->xml->notdienstzeiten . '</p>';

        date_default_timezone_set('Europe/Berlin');
        $now = time();

        $open_pharmacies = array();
        $closed_pharmacies = array();

        foreach ($this->notdienste->notdienst as $notdienst) {
            $opening_hours = explode('-', $notdienst->oeffnungszeiten);
            $opening_time = strtotime($opening_hours[0]);
            $closing_time = strtotime($opening_hours[1]);

            if ($now >= $opening_time && $now < $closing_time) {
                $notdienst->status = 'open';
                $open_pharmacies[] = $notdienst;
            } else {
                $notdienst->status = 'closed';
                $closed_pharmacies[] = $notdienst;
            }
        }

        if (!empty($open_pharmacies)) {
            foreach ($open_pharmacies as $notdienst) {
                $output .= '<div class="pharm-item">';
                $output .= '<div class="pharm-status open">' . $notdienst->status . '</div>';
                $output .= '<div class="pharm-details">';
                $output .= '<h4>' . esc_html($notdienst->apotheke) . '</h4>';
                $output .= '</div>';
                $output .= '</div>';
            }
        }

        if (!empty($closed_pharmacies)) {
            foreach ($closed_pharmacies as $notdienst) {
                $output .= '<div class="pharm-item">';
                $output .= '<div class="pharm-details">';
                $output .= '<h4>' . $notdienst->apotheke . '</h4>';
                $output .= '<div class="pharm-address"><p>' . $notdienst->strasse . '<br>' . $notdienst->plz . ' ' . $notdienst->ort . '</p><p><a href="tel:' . $notdienst->telefon . '">' . $notdienst->telefon . '</a></p><p><a href="https://www.google.com/maps/search/?api=1&query=' . str_replace(' ', '+', $notdienst->strasse . ', ' . $notdienst->plz . ' ' . $notdienst->ort) . '&query_place_id=' . $notdienst->place_id . '" target="_blank">' . esc_html__('Adresse öffnen in Google Maps', 'my-plugin') . '</a></p></div>';
                $output .= '</div>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';

        return $output;
    }


}
Pharmacies_Shortcode::init();

$pharmacies_shortcode = new Pharmacies_Shortcode();

add_shortcode('pharmacies_bzn', array($pharmacies_shortcode, 'get_pharmacies'));
