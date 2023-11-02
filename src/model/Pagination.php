<?php

require_once __DIR__ . "/Model.php";

class Pagination implements Model {

    private array $next = array(
    );
    private array $prev = array(
    );

    public function setNext(string $page, string $pageSize): void
    {
        $this->next["page"] = $page;
        $this->next["pageSize"] = $pageSize;
    }

    public function setPrev(string $page, string $pageSize): void
    {
        $this->prev["page"] = $page;
        $this->prev["pageSize"] = $pageSize;
    }

    function getData(): array
    {
        return array(
            "next" => $this->next,
            "prev" => $this->prev
        );
    }

}