<?php

require __DIR__ . "/../model/Community.php";
require __DIR__ . "/../model/Collection.php";
require __DIR__ ."/../model/Item.php";
require __DIR__ . "/../model/Bitstream.php";

class DataObjects {

    public function getCommunityModel(): Community
    {
        return new Community();
    }

    public function getCollectionModel(): Collection
    {
        return new Collection();
    }

    public function getItemModel() : Item {
        return new Item();
    }

    public function getBitstreamModel() : Bitstream {
        return new Bitstream();
    }
}
