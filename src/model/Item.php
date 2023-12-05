<?php

require_once __DIR__ . "/Model.php";

class Item implements Model {
    private string $name = "";
    private string $uuid = "";
    private string $title = "";
    private string $creator = "";
    private string $date = "";
    private string $description = "";
    private string $abstract = "";
    private string $owningCollectionHref = "";
    private string $owningCollectionName = "";
    private string $owningCollectionUuid  = "";
    private string $thumbnail = "";
    private string $rights = "";
    private string $rightsLink = "";

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function setUUID($uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setAbstract(string $abstract): void
    {
        $this->abstract = $abstract;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function setOwningCollectionHref(string $owningCollection): void
    {
        $this->owningCollectionHref = $owningCollection;
    }

    public function setThumbnail(string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    public function setAuthor(string $author): void
    {
        $this->creator = $author;
    }

    public function setBitstreams(array $bitstreams): void
    {
        $this->bitstreams = $bitstreams;
    }

    public function setOwningCollectionName(string $owningCollectionName): void
    {
        $this->owningCollectionName = $owningCollectionName;
    }

    public function setOwningCollectionUuid(string $owningCollectionUuid): void
    {
        $this->owningCollectionUuid = $owningCollectionUuid;
    }

    public function setRights(string $rights): void
    {
        $this->rights = $rights;
    }

    public function setRightsLink(string $rightsLink): void
    {
        $this->rightsLink = $rightsLink;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return array item metadata
     * <code>
     *     array("name" => string,
     *       "uuid" => string,
     *       "creator" => string,
     *       "date" => string,
     *       "description" => string,
     *       "owningCollection" => string,
     *       "logo" => string,
     *       "files" => array (
     *         "name" => string,
     *         "href" => string,
     *         "thumbnail" => string,
     *         "uuid" => string,
     *         "mimetype" => string
     *        )
     * )
     * </code>
     */
    public function getData(): array
    {
        $response = array(
            "name" => $this->name,
            "uuid" => $this->uuid,
        );
        if (strlen($this->title) > 0) {
            $response["metadata"]["title"] = $this->title;
        }
        if (strlen($this->description) > 0) {
            $response["metadata"]["description"] = $this->description;
        }
        if (strlen($this->abstract) > 0) {
            $response["metadata"]["abstract"] = $this->abstract;
        }
        if (strlen($this->creator) > 0) {
            $response["metadata"]["creator"] = $this->creator;
        }
        if (strlen($this->date) > 0) {
            $response["metadata"]["date"] = $this->date;
        }
        if (strlen($this->rights) > 0) {
            $response["metadata"]["rights"] = $this->rights;
        }
        if (strlen($this->rightsLink) > 0) {
            $response["metadata"]["rights.uri"] = $this->rightsLink;
        }
        if (strlen($this->owningCollectionHref) > 0) {
            $response["owningCollection"]["href"] = $this->owningCollectionHref;
        }
        if (strlen($this->owningCollectionName) > 0) {
            $response["owningCollection"]["name"] = $this->owningCollectionName;
        }
        if (strlen($this->owningCollectionUuid) > 0) {
            $response["owningCollection"]["uuid"] = $this->owningCollectionUuid;
        }
        if (strlen($this->thumbnail) > 0) {
            $response["thumbnail"] = $this->thumbnail;
        }
        return $response;
    }



}