<?php

require_once __DIR__ . "/Model.php";

class Bitstream implements Model {

    private string $name = "";
    private string $uuid = "";
    private string $href = "";
    private string $mimetype = "";
    private string $thumbnail = "";
    private string $label = "";
    private string $medium = "";
    private string $dimensions = "";
    private string $subject = "";
    private string $description = "";
    private string $type = "";

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setMedium(string $medium): void
    {
        $this->medium = $medium;
    }

    public function setDimensions(string $dimensions): void
    {
        $this->dimensions = $dimensions;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setMimetype(string $mimetype): void
    {
        $this->mimetype = $mimetype;
    }

    public function setHref(string $href): void
    {
        $this->href = $href;
    }


    public function setThumbnail(string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return array bitstream metadata
     * <code>
     * array (
     *   "name" => string,
     *   "uuid" => string,
     *   "mimetype" => string,
     *   "href" => string,
     *   "thumbnail" => string,
     *    "metqdata" => array(
     *      "title" => string,
     *      "label" => string,
     *      "medium" => string,
     *      "dimensions" => string,
     *      "subject" => string,
     *      "description" => string,
     *      "type" => string
     *    )
     *  )
     * </code>
     */
    public function getData(): array
    {
        return array (
            "name" => $this->name,
            "href" => $this->href,
            "uuid" => $this->uuid,
            "thumbnail" => $this->thumbnail,
            "mimetype" =>$this->mimetype,
            "metadata" => array(
                "label" => $this->label,
                "medium" => $this->medium,
                "dimensions" => $this->dimensions,
                "subject" => $this->subject,
                "description" => $this->description,
                "type" => $this->type
            )
        );
    }




}