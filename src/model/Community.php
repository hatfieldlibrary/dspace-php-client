<?php

require_once __DIR__ . "/Model.php";

class Community implements Model
{
    private string $name = "";
    private string $uuid = "";
    private string $logo = "";
    private string $subsectionCount = "undefined";


    public function __construct() {}

    /**
     * Set the name of the subcommunity.
     * @param $name
     * @return void
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * Set the DSpace uuid of the subcommunity.
     * @param $uuid
     * @return void
     */
    public function setUUID($uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Set the URL of the subcommunity logo.
     * @param $href
     * @return void
     */
    public function setLogo($href): void
    {
        $this->logo = $href;
    }

    /**
     * Set the number of collections in the subcommunity.
     * @param $subsectionCount
     * @return void
     */
    public function setSubsectionCount($subsectionCount): void
    {
        $this->subsectionCount = $subsectionCount;
    }

    /**
     * Get subcommunity data.
     * @return array
     * <code>
     *    array(
     *     "name" => $this->name,
     *     "uuid" => $this->uuid,
     *     "logo" => $this->logo,
     *     "count" => $this->count,
     * )
     * </code>
     */
    public function getData(): array
    {
        return array(
            "name" => $this->name,
            "uuid" => $this->uuid,
            "logo" => $this->logo,
            "subSectionCount" => $this->subsectionCount
        );
    }


}