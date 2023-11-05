<?php

Class Configuration {

    private array $config;

    public function __construct() {
        // DSpace API settings
        $this->config = array(
            /**
             * Base url of DSpace REST API
             */
            "base"=>"http://localhost:8080/server/api",

            "scope" => "602c3c60-55f8-4e2f-98bb-1f280a818bfe",
            /**
             * The maximum number of items returned in requests for DSpace objects (e.g. Items, Collections).
             * Currently this class does not support pagination.
             */
            "defaultPageSize" => 40,
            /**
             * The maximum number of embedded bitstreams (e.g. images) returned when retrieving images.
             */
            "defaultEmbeddedBitstreamParam" => 300,
            /**
             * Default image used in no thumbnail is available.
             */
            "defaultThumbnail" => "/mimi/images/pnca_mimi_default.jpeg",
            /**
             * When true all DSpace API requests and responses are written to the log file.
             * This is verbose. The value should be false when not actively debugging or developing.
             */
            "debug" => false
        );
    }

    public function getConfig(): array {
        return $this->config;
    }

}
