<?php

require_once __DIR__ . "/Model.php";

class Community implements Model
{
    private string $name = "";
    private string $uuid = "";
    private string $logo = "";
    private string $collectionCount = "0";
    private string $subSectionCount = "0";


    public function __construct() {}

    /**
     * Set the name of the subcommunity (section).
     * @param $name
     * @return void
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Set the DSpace uuid of the subcommunity (section).
     * @param $uuid
     * @return void
     */
    public function setUUID($uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Set the URL of the subcommunity (section) logo.
     * @param $href
     * @return void
     */
    public function setLogo($href): void
    {
        $this->logo = $href;
    }

    /**
     * Set the number of collections in the subcommunity (section).
     * @param string $collectionCount
     * @return void
     */
    public function setCollectionCount(string $collectionCount): void
    {
        $this->collectionCount = $collectionCount;
    }

    public function setSubSectionCount(string $subSectionCount): void
    {
        $this->subSectionCount = $subSectionCount;
    }

    /**
     * Get subcommunity (section) data.
     * @return array
     * <code>
     *    array(
     *     "name" => $this->name,
     *     "uuid" => $this->uuid,
     *     "logo" => $this->logo,
     *     "collectionCount" => string,
     *     "subsectionCount" => string,
     * )
     * </code>
     */
    public function getData(): array
    {
        $response = array(
            "name" => $this->name,
            "uuid" => $this->uuid,
            "collectionCount" => $this->collectionCount,
            "subsectionCount" => $this->subSectionCount
        );
        if (strlen($this->logo) > 0) {
            $response["logo"] = $this->logo;
        }
        return $response;
    }




}