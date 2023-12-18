<?php

require_once __DIR__ . "/Model.php";

class ObjectsList implements Model {

    private array $pagination = array();
    private array $objects = array();

    private string $count = "0";

    private string $query = "";

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

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    function getData(): array
    {
        $response = array(
            "pagination" => $this->pagination,
            "objects" => $this->objects,
            "count" => $this->count,

        );
        if (strlen($this->query) > 0) {
            $response["query"] = $this->query;
        }
        return $response;
    }

}