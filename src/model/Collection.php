<?php

require_once __DIR__ . "/Model.php";

class Collection implements Model {
    
    private $name = "";
    private $uuid = "";
    private $description = "";
    private $shortDescription = "";
    private $logo = "";

    private int $count = 0;

    /**
     * Set the name of the community.
     * @param $name
     * @return void
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Set the DSpace uuid of the community.
     * @param $uuid
     * @return void
     */
    public function setUUID($uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setDescription($desc): void
    {
        $this->description = $desc;
    }

    public function setShortDescription($desc): void
    {
        $this->shortDescription = $desc;
    }
    
    /**
     * Set the URL of the community logo.
     * @param $href
     * @return void
     */
    public function setLogo($href): void
    {
        $this->logo = $href;
    }

    /**
     * Set the number of collections in the community.
     * @param $count
     * @return void
     */
    public function setCount($count): void
    {
        $this->count = $count;
    }



    /**
     * Get collection data.
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
            "itemCount" => $this->count,
        );
    }
    
}
