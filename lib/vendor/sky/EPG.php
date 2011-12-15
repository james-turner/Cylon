<?php

class EPG {

    private $domain = 'epgservices.sky.com';
    private $client_id = 5;
    private $client_service_id = 1;
    private $client_service_version = 1;
    private $api_version = 2.1;
    private $format = 'json';

    private $channel_list_uri = 'http://epgservices.sky.com/5.1.1/api/2.1/region/json/4097/1/';

    private $channel_info_uri = 'http://epgservices.sky.com/5.1.1/api/2.1/channel/json/{channel_ids}/now/n/1';

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

        $channel_ids = array();
        foreach($channels["init"]["channels"] as $channel){
            $channel_id = $channel["c"][0];
            $channel_name = $channel["t"];
            $channel_ids[] = $channel_id;

            self::$epg[$channel_id] = array('name'=>$channel_name);
        }

        $channel_count = count(array_keys(self::$epg));
        $listings = array();
        $increment = 20;
        for($i=0; $i<$channel_count; $i+=$increment){

            $slice = array_slice($channel_ids, $i, $increment, true);
            $json = file_get_contents(strtr($this->channel_info_uri, array('{channel_ids}'=>implode(',', $slice))));

            $info = json_decode($json, true);

            foreach($info["listings"] as $id => $data){
                $listings[$id] = $data[0];
            }
        }

        foreach(self::$epg as $channel_id => $info){

            if(isset($listings["$channel_id"])){
                $listing = $listings["$channel_id"];
                $now_playing = $listing["t"];
                $img = @$listing["img"];
                $url = @$listing["url"];

                self::$epg[$channel_id]["now_playing"] = $now_playing;
                self::$epg[$channel_id]["img"] = $img;
                self::$epg[$channel_id]["url"] = $url;
            }

        }

        // store it!
        //file_put_contents($this->store, json_encode(self::$epg));

        return self::$epg;

    }

}