# DSpace PHP REST
PHP REST API and client for read-only DSpace API information.

The client provides functions for DSpace API queries to retrieve Community, Collection, Item and Bitstream information.

The client returns only "essential" information about DSpace objects. The goal is to provide a simple tool for 
constructing websites that incorporate information from the DSpace repository, omitting the more extensive 
information needed to create a fully-featured DSpace user interface. 

## Endpoints

__/api/endpoints__

List of endpoints.

___

__/api/topevel__

Gets information about the top-level communities (sections)

Sample JSON response:

```agsl
{
  pagination: {
    next: {
      page: "1",
      pageSize: "2"
    },
    prev: {}
  },
  objects: {
    "Community One": {
      name: "Community One",
      uuid: "210d5023-daa6-4e82-a62b-2749cfd4c61d",
      logo: "http://localhost:8080/server/api/core/bitstreams/04062743-0e22-48cd-967c-239d075000ea/content",
      subSectionCount: "0"
    },
    "Community Two": {
      name: "Community Two",
      uuid: "35eaa237-3e86-40a2-9973-de61914ac080",
      logo: "http://localhost:8080/server/api/core/bitstreams/ccd3874b-2b68-476b-bf21-09365f866b76/content",
      subSectionCount: "2"
    } 
  },
  count: 4
}
```
___

__/api/sections/<:uuid>__

Gets information about a specific community (section).

###Parameters:

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)

Sample JSON response:

```
{
name: "Community Name",
uuid: "602c3c60-55f8-4e2f-98bb-1f280a818bfe",
logo: "/mimi/images/default.jpeg",
count: "undefined"
}
```
___

__/api/sections/<:uuid>/subsections__

Gets information about subsections the section with the provided uuid.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)

**page:** page<br>
**in:** query<br>
**required:** false<br>
**description:** The current page in pagination (default = 0)

**page:** size<br>
**in:** query<br>
**required:** false<br>
**description:** The current page size used in pagination (default defined in Configuration).

Sample JSON response:

```
{
  pagination: {
    next: {
      page: "1",
      pageSize: "2"
    },
    prev: {}
  },
  objects: {
    "Community One": {
      name: "Community One",
      uuid: "210d5023-daa6-4e82-a62b-2749cfd4c61d",
      logo: "http://localhost:8080/server/api/core/bitstreams/04062743-0e22-48cd-967c-239d075000ea/content",
      subSectionCount: "0"
    },
    "Community Two": {
      name: "Community Two",
      uuid: "35eaa237-3e86-40a2-9973-de61914ac080",
      logo: "http://localhost:8080/server/api/core/bitstreams/ccd3874b-2b68-476b-bf21-09365f866b76/content",
      subSectionCount: "2"
    } 
  },
  count: "4"
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

**page:** page<br>
**in:** query<br>
**required:** false<br>
**description:** The current page in pagination (default = 0)

**page:** size<br>
**in:** query<br>
**required:** false<br>
**description:** The current page size used in pagination (default defined in Configuration).

Sample JSON response:

```
{
  pagination: {
    next: {
      page: "1",
      pageSize: "2"
    },
    prev: {}
  },
  objects: [
    {
      name: "Collection One",
      uuid: "1eb8d02b-3cf7-459c-a37f-dc2122a75a1f",
      logo: "http://localhost:8080/server/api/core/bitstreams/00a76cd4-08dc-4c53-96f8-2111c110c6b4/content"
    },
    {
      name: "Collection Two",
      uuid: "1eb8d02b-3cf7-459c-a37f-dc2122a75a1f",
      logo: "http://localhost:8080/server/api/core/bitstreams/00a76cd4-08dc-4c53-96f8-2111c110c6b4/content"
    }
  ],
  count: "4"
}
```
## /api/collections/<:uuid>

Information about a specific DSpace collection. Includes count of the 
number of items in the collection.

### Parameters

**name:** uuid<br>
**in:** path<br>
**required:** true<br>
**description:** The DSpace uuid of the community (section)

Sample JSON response:

```
{
  name: "Collection",
  uuid: "0b6eba01-5bef-440f-a950-b29dd37db505",
  description: "This is the description of the DSpace collection.",
  shortDescription: "This is the short description of the DSpace collection.",
  logo: "",
  itemCount: 3
}
```