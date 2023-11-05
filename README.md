# DSpace PHP REST
PHP REST API and client for read-only DSpace API information.

The client provides functions for DSpace API queries to retrieve Community, Collection, Item and Bitstream information.

The client returns only "essential" information about DSpace objects. The goal is to provide a simple tool for 
constructing websites that incorporate information from the DSpace repository, omitting the more extensive 
information needed to create a fully-featured DSpace user interface. 

# Endpoints

## /api/endpoints

List of endpoints.

___

## /api/toplevel

Gets information about the top-level communities (sections)

Sample JSON response:

```json
{
  "pagination": {
    "next": [
      {
        "page": 1,
        "size": 5
      }
    ],
    "prev": [ ]
  },
  "objects": {
    "Section One": {
      "name": "Section One",
      "uuid": "602c3c60-55f8-4e2f-98bb-1f280a818bfe",
      "logo": "/images/default.jpeg",
      "collectionCount": "0",
      "subsectionCount": "4"
    },
    "Section Two": {
      "name": "Section Two",
      "uuid": "ce894cbb-65eb-4642-83e7-81f2fda2cec0",
      "logo": "http://localhost:8080/server/api/core/bitstreams/ccd3874b-2b68-476b-bf21-09365f866b76/content",
      "collectionCount": "1",
      "subsectionCount": "1"
    }
  },
  "count": "10"
}

```

___

## /api/sections/<:uuid>

Gets information about a specific community (section).

###Parameters:

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)
 
Sample JSON response:

```json
{
   "name": "Section Name",
   "uuid": "602c3c60-55f8-4e2f-98bb-1f280a818bfe",
   "logo": "/images/default.jpeg",
   "collectionCount": "0",
   "subsectionCount": "4"
}

```

___


## /api/sections/<:uuid>/subsections

Gets list of subsections within the section with the provided uuid.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)

**name:** page<br>
**in:** query<br>
**required:** false<br>
**description:** The current page in pagination (default = 0)

**name:** size<br>
**in:** query<br>
**required:** false<br>
**description:** The current page size used for pagination (default defined in Configuration).

Sample JSON response:

```json
{
  "pagination": {
    "next": {
      "page": "1",
      "pageSize": "2"
    },
    "prev": {}
  },
  "objects": {
    "Section One": {
      "name": "Section One",
      "uuid": "210d5023-daa6-4e82-a62b-2749cfd4c61d",
      "logo": "http://localhost:8080/server/api/core/bitstreams/04062743-0e22-48cd-967c-239d075000ea/content",
      "collectionCount": "1",
      "subsectionCount": "1"
    },
    "Section Two": {
      "name": "Section Two",
      "uuid": "35eaa237-3e86-40a2-9973-de61914ac080",
      "logo": "http://localhost:8080/server/api/core/bitstreams/ccd3874b-2b68-476b-bf21-09365f866b76/content",
      "collectionCount": "0",
      "subsectionCount": "4"
    } 
  },
  "count": "4"
}

```

___


## /api/sections/<:uuid>/collections

List of the collections within the section with the provided uuid.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)

**name:** page<br>
**in:** query<br>
**required:** false<br>
**description:** The current page in pagination (default = 0)

**name:** size<br>
**in:** query<br>
**required:** false<br>
**description:** The current page size used for pagination (default defined in Configuration).

Sample JSON response:

```json
{
  "pagination": {
    "next": [ ],
    "prev": [ ]
  },
  "objects": [
    {
      "name": "Collection One",
      "uuid": "0b6eba01-5bef-440f-a950-b29dd37db50",
      "description": "Collection One full description.",
      "shortDescription": "Collection One short description",
      "logo": "http://localhost:8080/server/api/core/bitstreams/291c1a8c-3475-4194-851c-6639f02a8331/content",
      "itemCount": "3"
    },
    {
      "name": "Collection Two",
      "uuid": "1eb8d02b-3cf7-459c-a37f-dc2122a75a1f",
      "description": "Collection Two full description..",
      "shortDescription": "Collection Two brief description.",
      "logo": "http://localhost:8080/server/api/core/bitstreams/00a76cd4-08dc-4c53-96f8-2111c110c6b4/content", 
      "itemCount": "0"
    }
  ],
  "count": "2"
}

```

___

## /api/collections/<:uuid>

Information about a specific DSpace collection. Includes the 
number of items in the collection.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the collection.

Sample JSON response:

```json
{
  "name": "Collection",
  "uuid": "0b6eba01-5bef-440f-a950-b29dd37db505",
  "description": "This is the description of the DSpace collection.",
  "shortDescription": "This is the short description of the DSpace collection.",
  "logo": "http://localhost:8080/server/api/core/bitstreams/291c1a8c-3475-4194-851c-6639f02a8331/content",
  "itemCount": 3
}

```

___

## /api/collections/<:uuid>/items

List of items within a specific DSpace collection with the provided uuid. 

## Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)

**name:** page<br>
**in:** query<br>
**required:** false<br>
**description:** The current page in pagination (default = 0)

**name:** size<br>
**in:** query<br>
**required:** false<br>
**description:** The current page size used for pagination (default defined in Configuration).

Sample JSON Response:

```json
{
  "pagination": {
    "next": [ ],
    "prev": [ {
        "page": "0",
        "pageSize": "2"
      }
    ]
  }, 
  "objects": [
    {
      "name": "Item Title", 
      "uuid": "b30f3383-8653-4114-abaa-b642a6e535a1", 
      "creator": "John Doe", 
      "date": "2023", 
      "description": "Description of item.", 
      "owningCollection": "http://localhost:8080/server/api/core/items/b30f3383-8653-4114-abaa-b642a6e535a1/owningCollection", 
      "logo": "http://localhost:8080/server/api/core/bitstreams/3a869688-3cfe-4074-95f5-706749f8e9d0/content"
    }, 
    {
      "name": "Item Title", 
      "uuid": "b1b4aff9-1572-4e4d-be4d-a6216cc52d3f", 
      "creator": "Julie Doe", 
      "date": "2023", 
      "description": "Description of item.",
      "owningCollection": {
        "href": "http://localhost:8080/server/api/core/items/b30f3383-8653-4114-abaa-b642a6e535a1/owningCollection"
      },
      "logo": "http://localhost:8080/server/api/core/bitstreams/1008de2c-069f-4a01-8ef5-9b7fe0df1e92/content"
    }
  ], 
  "count": "4"
}

```
___

## /api/items/<:uuid>

Information about a specific DSpace item. 

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the item.

Sample JSON Response:

```json
{
  "name":"Sample Item",
  "uuid":"b30f3383-8653-4114-abaa-b642a6e535a1",
  "creator":"John Doe",
  "date":"2023",
  "description":"Description of the item.",
  "owningCollection":{
    "href": "http://localhost:8080/server/api/core/collections/0b6eba01-5bef-440f-a950-b29dd37db505",
    "name": "Owning Collection Name",
    "uuid": "0b6eba01-5bef-440f-a950-b29dd37db505"
  },
  "thumbnail":"http:\/\/localhost:8080\/server\/api\/core\/bitstreams\/3a869688-3cfe-4074-95f5-706749f8e9d0\/content"
}

```
___

## /api/items/<:uuid>/files

Gets list of files for item with the provided DSpace uuid.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the item.

Sample JSON response:

```json
{
  "pagination":[],
  "objects": [
    {
      "name": "roger-11.jpeg",
      "href": "http:\/\/localhost:8080\/server\/api\/core\/bitstreams\/1ba87266-e790-475a-b5ea-d51b2b5b5ae0\/content",
      "uuid": "1ba87266-e790-475a-b5ea-d51b2b5b5ae0",
      "thumbnail": "http:\/\/localhost:8080\/server\/api\/core\/bitstreams\/1ba87266-e790-475a-b5ea-d51b2b5b5ae0\/content",
      "mimetype": "image\/jpeg",
      "metadata": {
        "title": "roger-11.jpeg",
        "label": "The best photo.",
        "medium": "Digital",
        "dimensions": "13\" x 17\"",
        "subject": "surrealism",
        "description": "Image 1",
        "type": "Illustration"
      } 
    }], 
  "count": "1"
}

```
___

## /api/files/<:uuid>

Information about the file with the provided DSpace uuid.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the file.

Sample JSON response:

```json
{
  "name":"roger-10.jpeg",
  "href":"http:\/\/localhost:8080\/server\/api\/core\/bitstreams\/a6c0a357-8901-45fd-9a3d-4616233106f8\/content",
  "uuid":"a6c0a357-8901-45fd-9a3d-4616233106f8",
  "thumbnail":"http:\/\/localhost:8080\/server\/api\/core\/bitstreams\/a6c0a357-8901-45fd-9a3d-4616233106f8\/content",
  "mimetype":"image\/jpeg",
  "metadata": {
    "title":"roger-10.jpeg",
    "label":"The View",
    "medium":"Digital",
    "dimensions":"13\" x 17\"",
    "subject":"cosmology",
    "description":"Photo of universe",
    "type":"Illustration"
  }
}

```

___

## /api/search

Search for DSpace items, collections and communities (sections). Response includes the
type of DSpace object (item, collection, or community).

## Parameters

**name:** query<br>
**in:** query<br>
**required:** true<br>
**description:** The query term

**name:** scope<br>
**in:** query<br>
**required:** true<br>
**description:** The query scope (collection or section uuid)

**name:** page<br>
**in:** query<br>
**required:** false<br>
**description:** The current page in pagination (default = 0)

**name:** size<br>
**in:** query<br>
**required:** false<br>
**description:** The current page size used for pagination (default defined in Configuration).

Sample JSON response:

```json
{
  "pagination": {
    "next": [ ],
    "prev": [ ]
  },
  "objects": [
    {
      "name": "Jane's Thesis Spring 2023",
      "uuid": "ae44806f-b854-4de9-8113-100494d81997",
      "type": "item",
      "metadata": {
       "title": "My Thesis",
       "creator": "Jane Doe",
       "description": "Description of my thesis.",
       "date": "2023"
      },
      "thumbnail": {
        "name": "jane-1.jpeg.jpg",
        "href": "http://localhost:8080/server/api/core/bitstreams/ecd9b20c-86d7-4a6a-b0bf-306cae8d3049/content"
      }
    },
    {
      "name": "Thesis",
      "uuid": "35eaa237-3e86-40a2-9973-de61914ac080",
      "type": "community",
      "metadata": {
        "title": "Thesis",
        "creator": "",
        "description": "Description of the Thesis collection.",
        "date": ""
      },
      "thumbnail": {
        "name": "",
        "href": ""
      }
    }
  ],
  "count": "2"
}

```