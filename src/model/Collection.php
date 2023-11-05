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
     *      "description" => string | undefined,
     *      "shortDescription" => string | undefined,
     *      "logo" => string | undefined,
     *      "count" => string
     * )
     * </code>
     */
    public function getData() : array
    {
        $response = array(
            "name" => $this->name,
            "uuid" => $this->uuid,
            "itemCount" => $this->itemCount,
        );
        if (strlen($this->shortDescription) > 0) {
            $response["shortDescription"] = $this->shortDescription;
        }
        if (strlen($this->description) > 0) {
            $response["description"] = $this->description;
        }
        if (strlen($this->logo) > 0) {
            $response["logo"] = $this->logo;
        }
        return $response;

    }


}
