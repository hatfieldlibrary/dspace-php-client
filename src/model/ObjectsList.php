<?php

require_once __DIR__ . "/Model.php";

class ObjectsList implements Model {

    private array $pagination = array();
    private array $objects = array();

    private string $count = "0";

    public function setPagination(array $pagination): void
    {
        $this->pagination = $pagination;
    }

    public function setObjects(array $objects): void
    {
        $this->objects = $objects;
    }

    public function setCount(string $count): void
    {
        $this->count = $count;
    }

    function getData(): array
    {
        // single object conversion to array
        if (count($this->objects) == 1) {
            $this->objects = [$this->objects];
        }

        return array (
            "pagination" => $this->pagination,
            "objects" => $this->objects,
            "count" => $this->count
        );
    }
}