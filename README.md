# DSpace PHP REST

This is basically a facade that offers a simplied view of the DSpace REST API. It is meant to ease the integration of public DSpace content into other sites.  Requires PHP 8. No PHP REST API framework is used.

The API endpoints retrieve Community, Collection, Item and Bitstream information from the DSpace REST API and return simplified responses. The responses contain only "essential" information about DSpace objects. The goal is to provide an easy-to-use service
for constructing websites, omitting the more extensive information needed to create a fully-featured DSpace 
user interface. 

The `DSpaceDataService` implementation can also be included in a PHP webpage and used to access DSpace
content without using the PHP API.

**Contents:**
- [Usage](#usage)
- Endpoints
  - [Top Level Sections](#toplevel)
  - [Single Section](#section)
  - [List of Subsections](#subsections)
  - [List of Collections in Section](#sections-collections)
  - [Single Collection](#collections)
  - [List of Items in Collection](#items-collections)
  - [Single Item](#item)
  - [List of Files for Item](#item-files)
  - [Single File](#single-file)
  - [Search Queries](#search)
  - [Count of Items in Collection](#itemcount)
- [Pagination](#pagination)
- [Configuration](#config)

<a id="usage"></a>
# Usage

Simple example: copy the contents of the source directory to the web server and use this URL:

http://localhost/example/index.php/api/endpoints

Apache configuration example: To simplify the url and get rid of `index.php` you can add the following to your Apache configuration for the base directory. This example
assumes the base directory for the application is `/example` and redirects all requests to `index.php`.

```
RewriteEngine On
RewriteBase /example
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.+)$ index.php [QSA,L]
```

Here's the new request URL:

http://localhost/example/api/endpoints

If it's a production site, you should probably add a cache-control header to your Apache config as well.


# Endpoints

## /api/endpoints

List of endpoints.

___

<a id="toplevel"></a>
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

<a id="section"></a>
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


<a id="subsections"></a>
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


<a id="sections-collections"></a>
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

<a id="collections"></a>
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

<a id="items-collections"></a>
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
      "name": "Item One Title", 
      "uuid": "b30f3383-8653-4114-abaa-b642a6e535a1", 
      "metadata": {
        "title": "Item One Title",
        "creator": "John Doe",
        "date": "2023",
        "description": "Description of item."
      },
      "owningCollection": "http://localhost:8080/server/api/core/items/b30f3383-8653-4114-abaa-b642a6e535a1/owningCollection", 
      "logo": "http://localhost:8080/server/api/core/bitstreams/3a869688-3cfe-4074-95f5-706749f8e9d0/content"
    }, 
    {
      "name": "Item Two Title", 
      "uuid": "b1b4aff9-1572-4e4d-be4d-a6216cc52d3f",
      "metadata": {
        "title": "Item Two Title",
        "creator": "Julie Doe",
        "date": "2023",
        "description": "Description of item."
      },
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

<a id="item"></a>
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
  "metadata" :{
    "description": "Description of item ...</p>",
    "creator": "Jane Doe",
    "date": "2023",
    "rights": "Educational use allowed.",
    "rights.uri": "https://rights.edu"
  },
  "owningCollection":{
    "href": "http://localhost:8080/server/api/core/collections/0b6eba01-5bef-440f-a950-b29dd37db505",
    "name": "Owning Collection Name",
    "uuid": "0b6eba01-5bef-440f-a950-b29dd37db505"
  },
  "thumbnail":"http:\/\/localhost:8080\/server\/api\/core\/bitstreams\/3a869688-3cfe-4074-95f5-706749f8e9d0\/content"
}

```
___

<a id="item-files"></a>
## /api/items/<:uuid>/files

Gets list of files for the item with the provided DSpace uuid.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the item.

**name:** bundle<br>
**in:** query<br>
**required:** false<br>
**description:** Name of alternate bundle for images. 

Note that if thumbnail images for the individual files don't exist in DSpace a default 
image will be used.

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
        "type": "Illustration",
        "rights": "Educational use allowed.",
        "rights.uri": "https://rights.edu"
      } 
    }], 
  "count": "1"
}

```
___

<a id="single-file"></a>
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

<a id="search"></a>
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
        "description": "Section Name description ..."
      }
    }
  ],
  "count": "2"
}

```

<a id="itemcount"></a>
## /api/collections/<:uuid>/itemcount

Returns the count of items for the collection with the provided uuid. This
is a special endpoint that can be used to request counts asynchronously.
See Configuration parameter "retrieveItemCounts".

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the collection.

Sample JSON response:

```json
"180"
```

<a id="pagination"></a>
# Pagination

List endpoints accept pagination parameters. 

This is a sample pagination element from a response. Both next and previous results are available. 

```json{
  "pagination": {
    "next": [ 
       "page" : "2",
       "size": "20"
       ]
    "prev": [ 
    "page" : "0",
     "size": "20"
     ]
  }
  
```

You can use this information to make next and previous page requests.

For example, to request the next page of search results:

```html
http://localhost/api/search?query=test+query&page=2&size=20
```

<a id="config"></a>
# Configuration

```
/**
* Base url of DSpace REST API
*/
"base"=>"http://localhost:8080/server/api",
/**
* The default DSpace scope. Used for search.
*/
"scope" => "602c3c60-55f8-4e2f-98bb-1f280a818bfe",
/**
* The maximum number of items returned in requests for DSpace objects (e.g. Items, Collections).
* Currently this class does not support pagination.
*/
"defaultPageSize" => 40,
/**
* The maximum number of embedded bitstreams (e.g. images) returned when retrieving images.
* The bitstreams are retrieved as embedded elements in the bundle. Pagination is not currently
* supported by this application.
*/
"defaultEmbeddedBitstreamParam" => 60,
/**
* Default image used in no thumbnail is available.
*/
"defaultThumbnail" => "/mimi/images/pnca_mimi_default.jpeg",
/**
* Retrieve item counts for collections. There may be a performance hit when set to true.
* Item counts can also be retrieved asynchronously via the <code>api/collections/<:uuid>/itemcount</code>
* endpoint.
*/
"retrieveItemCounts" => true,
/**
* When true all DSpace API requests and responses are written to the log file.
* This is verbose. The value should be false when not actively debugging or developing.
*/
"debug" => false

```
