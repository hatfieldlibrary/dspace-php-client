<?php

require_once __DIR__ . "/Model.php";

class Bitstream implements Model {

    private string $name = "";
    private string $uuid = "";
    private string $href = "";
    private string $mimetype = "";
    private string $thumbnail = "";
    private string $title = "";
    private string $label = "";
    private string $medium = "";
    private string $dimensions = "";
    private string $subject = "";
    private string $description = "";
    private string $type = "";
    private string $rights = "";
    private string $rightsLink = "";

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

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setRights(string $rights): void
    {
        $this->rights = $rights;
    }

    public function setRightsLink(string $rightsLink): void
    {
        $this->rightsLink = $rightsLink;
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
        $response = array(
            "name" => $this->name,
            "href" => $this->href,
            "uuid" => $this->uuid,
            "mimetype" =>$this->mimetype,
        );
        if (strlen($this->thumbnail) > 0)  {
            $response["thumbnail"] = $this->thumbnail;
        }
        if (strlen($this->title) > 0)  {
            $response["metadata"]["title"] = $this->title;
        }
        if (strlen($this->label) > 0)  {
            $response["metadata"]["label"] = $this->label;
        }
        if (strlen($this->medium) > 0)  {
            $response["metadata"]["medium"] = $this->medium;
        }
        if (strlen($this->dimensions) > 0)  {
            $response["metadata"]["dimensions"] = $this->dimensions;
        }
        if (strlen($this->subject) > 0)  {
            $response["metadata"]["subject"] = $this->subject;
        }
        if (strlen($this->description) > 0)  {
            $response["metadata"]["description"] = $this->description;
        }
        if (strlen($this->type) > 0)  {
            $response["metadata"]["type"] = $this->type;
        }
        if (strlen($this->rights) > 0) {
            $response["metadata"]["rights"] = $this->rights;
        }
        if (strlen($this->rightsLink) > 0) {
            $response["metadata"]["rights.uri"] = $this->rightsLink;
        }
        return $response;
    }

}