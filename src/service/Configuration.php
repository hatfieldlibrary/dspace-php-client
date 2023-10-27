<?php

Class Configuration {

    private $config;

    public function __construct() {
        // DSpace API settings
        $this->config = array(
            /**
             * Base url of DSpace REST API
             */
            "base"=>"http://localhost:8080/server/api",
            /**
             * The maximum number of items returned in requests for DSpace objects (e.g. Items, Collections).
             * Currently this class does not support pagination.
             */
            "defaultPageSize" => 40,
            /**
             * The maximum number of embedded bitstreams (e.g. images) returned when retrieving images.
             */
            "defaultEmbeddedBitstreamParam" => "bitstreams=30",
            /**
             * Default image used in no thumbnail is available. (deprecated)
             */
            "defaultThumbnail" => "/mimi/images/pnca_mimi_default.jpeg",
            /**
             * Set to true if you want to log missing bitstream metadata.
             */
            "debug" => false
        );
    }

    public function getConfig(): array {
        return $this->config;
    }

}
