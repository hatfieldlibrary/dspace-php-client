<?php

interface SolrDataService
{
    public function search(array $params = []): string;

}

