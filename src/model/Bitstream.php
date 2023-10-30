<?php

require_once __DIR__ . "/Model.php";

class Bitstream implements Model {

    private string $title = "";
    private string $label = "";
    private string $medium = "";
    private string $dimensions = "";
    private string $subject = "";
    private string $description = "";
    private string $type = "";

    public function setTitle(string $title): void
    {
        $this->title = $title;
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

    /**
     * @return array bitstream metadata
     * <code>
     * array (
     *   "title" => string,
     *   "label" => string,
     *   "medium" => string,
     *   "dimensions" => string,
     *   "subject" => string,
     *   "description" => string,
     *   "type" => string
     *  )
     * </code>
     */
    public function getData(): array
    {
        return array (
            "title" => $this->title,
            "label" => $this->label,
            "medium" => $this->medium,
            "dimensions" => $this->dimensions,
            "subject" => $this->subject,
            "description" => $this->description,
            "type" => $this->type
        );
    }
}