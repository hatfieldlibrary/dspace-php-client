<?php

require_once __DIR__ . "/Model.php";

class Collection implements Model {
    
    private $name = "";
    private $uuid = "";
    private $description = "";
    private $shortDescription = "";
    private $logo = "";
    private $count = 0;

    /**
     * Set the name of the community.
     * @param $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the DSpace uuid of the community.
     * @param $uuid
     * @return void
     */
    public function setUUID($uuid) {
        $this->uuid = $uuid;
    }

    public function setDescription($desc) {
        $this->description = $desc;
    }

    public function setShortDescription($desc) {
        $this->shortDescription = $desc;
    }
    
    /**
     * Set the URL of the community logo.
     * @param $href
     * @return void
     */
    public function setLogo($href) {
        $this->logo = $href;
    }

    /**
     * Set the number of collections in the community.
     * @param $count
     * @return void
     */
    public function setCount($count) {
        $this->count = $count;
    }

    /**
     * Get community data.
     * @return array
     * <code>
     *    array(
     *      "name" => string,
     *      "uuid" => string,
     *      "description" => string,
     *      "shortDescription" => string,
     *      "logo" => string,
     *      "count" => int
     * )
     * </code>
     */
    public function getData() : array
    {
        return array(
            "name" => $this->name,
            "uuid" => $this->uuid,
            "description" => $this->description,
            "shortDescription" => $this->shortDescription,
            "logo" => $this->logo,
            "count" => $this->count
        );
    }
    
}
