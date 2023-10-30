<?php

require_once __DIR__ . "/Model.php";

class Community implements Model
{
    private $name = "";
    private $uuid = "";
    private $logo = "";
    private $count = 0;

    public function __construct() {}

    /**
     * Set the name of the subcommunity.
     * @param $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Set the DSpace uuid of the subcommunity.
     * @param $uuid
     * @return void
     */
    public function setUUID($uuid) {
        $this->uuid = $uuid;
    }

    /**
     * Set the URL of the subcommunity logo.
     * @param $href
     * @return void
     */
    public function setLogo($href) {
        $this->logo = $href;
    }

    /**
     * Set the number of collections in the subcommunity.
     * @param $count
     * @return void
     */
    public function setCount($count) {
        $this->count = $count;
    }

    /**
     * Get subcommunity data.
     * @return array
     * <code>
     *    array(
     *     "name" => $this->name,
     *     "uuid" => $this->uuid,
     *     "logo" => $this->logo,
     *     "count" => $this->count
     * )
     * </code>
     */
    public function getData(): array
    {
        return array(
            "name" => $this->name,
            "uuid" => $this->uuid,
            "logo" => $this->logo,
            "count" => $this->count
        );
    }
}