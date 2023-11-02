<?php

require_once __DIR__ . "/Model.php";

class ObjectsList implements Model {

    private array $pagination = array();
    private array $objects = array();

    public function setPagination(array $pagination): void
    {
        $this->pagination = $pagination;
    }

    public function setObjects(array $objects): void
    {
        $this->objects = $objects;
    }

    function getData(): array
    {
        return array (
            "pagination" => $this->pagination,
            "objects" => $this->objects
        );
    }


}