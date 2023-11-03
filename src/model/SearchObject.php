<?php

require_once __DIR__ . "/Model.php";

class SearchObject implements Model {
   private string $name = "";
   private string $uuid = "";
   private string $type = "";
   private string $thumbnailName = "";
   private string $thumbnailHref = "";
   private string $description = "";
   private string $date  = "";
   private string $creator = "";
   private string $title = "";

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setThumbnailName(string $thumbnailName): void
    {
        $this->thumbnailName = $thumbnailName;
    }

    public function setThumbnailHref(string $thumbnailHref): void
    {
        $this->thumbnailHref = $thumbnailHref;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    function getData(): array
    {
        return array(
            "name" => $this->name,
            "uuid" => $this->uuid,
            "type" => $this->type,
            "metadata" => array (
                "title" => $this->title,
                "creator" => $this->creator,
                "description" => $this->description,
                "date" => $this->date
            ),
            "thumbnail" => array (
                "name" => $this->thumbnailName,
                "href" => $this->thumbnailHref
            )
        );
    }

}
