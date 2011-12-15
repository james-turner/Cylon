<?php

class EPG {

    private $domain = 'epgservices.sky.com';
    private $client_id = 5;
    private $client_service_id = 1;
    private $client_service_version = 1;
    private $api_version = 2.1;
    private $format = 'json';

    private $channel_list_uri = 'http://epgservices.sky.com/5.1.1/api/2.1/region/json/4097/1/';

    private $channel_info_uri = 'http://epgservices.sky.com/5.1.1/api/2.1/channel/json/{channel_id}/now/n/1';

    private static $epg = array();

    private $store;

    public function __construct(){

        $this->store = dirname(__FILE__). "/../../../cache/epg.json";

        if(file_exists($this->store)){
            $json = json_decode(file_get_contents($this->store), true);
            if($json !== false){
                self::$epg = $json;
            }
        }
    }


    public function currentPlayingChannels(){

        $json = file_get_contents($this->channel_list_uri);
        $channels = json_decode($json, true);

        if(!empty(self::$epg)){
            return self::$epg;
        }

        $counter = 0;
        foreach($channels["init"]["channels"] as $channel){

            $channel_id = $channel["c"][0];
            $channel_name = $channel["t"];

            $now_playing = '';

            $json = file_get_contents(strtr($this->channel_info_uri, array('{channel_id}'=>$channel_id)));
            $info = json_decode($json, true);

            foreach($info["listings"]["$channel_id"] as $listing){
                $now_playing = $listing["t"];
            }

            self::$epg[$channel_id] = array('name'=>$channel_name, 'now_playing' => $now_playing);

        }

        // store it!
        file_put_contents($this->store, json_encode(self::$epg));

        return self::$epg;

    }

}