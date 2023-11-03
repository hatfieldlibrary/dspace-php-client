<?php

require __DIR__ . "/../model/Community.php";
require __DIR__ . "/../model/Collection.php";
require __DIR__ ."/../model/Item.php";
require __DIR__ . "/../model/Bitstream.php";
require __DIR__ . "/../model/Pagination.php";
require __DIR__ . "/../model/ObjectsList.php";
require __DIR__ . "/../model/SearchObject.php";

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

    public function getPaginationModel() : Pagination {
        return new Pagination();
    }

    public function getObjectsList() : ObjectsList {
        return new ObjectsList();
    }

    public function getSearchObject() : SearchObject {
        return new SearchObject();
    }
}
