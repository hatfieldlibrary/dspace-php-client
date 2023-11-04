<?php

require_once __DIR__ . "/Model.php";

class Collection implements Model {
    
    private $name = "";
    private $uuid = "";
    private $description = "";
    private $shortDescription = "";
    private $logo = "";
    private string $itemCount = "0";

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
     * Set the number of items in the collection.
     * @param string $itemCount
     * @return void
     */
    public function setItemCount(string $itemCount): void
    {
        $this->itemCount = $itemCount;
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
     *      "count" => string
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
            "itemCount" => $this->itemCount,
        );
    }


}
