# dspace-php-client
PHP client for read-only DSpace API information.

The client provides functions for DSpace API queries to retrieve Community, Collection, Item and Bitstream information.

The client returns only "essential" information about DSpace objects. The goal is to provide a simple tool for 
constructing websites that incorporate information from the DSpace repository, omitting the more extensive 
information needed to create a fully-featured DSpace user interface. 

## Endpoints

__/api/endpoints__

List of endpoints.

__/api/sections/<:uuid>__

Gets information about a specific community (section).

The JSON response:

```
{
name: "Community Name",
uuid: "602c3c60-55f8-4e2f-98bb-1f280a818bfe",
logo: "/mimi/images/default.jpeg",
count: "undefined"
}
```


__/api/sections/<:uuid>/subsections__

Gets information about subsections within this section.

The JSON response:

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
  }
}
```

__/api/sections/<:uuid>/collections__

List of the collections within this section.

The JSON response:

```
{
  pagination: {
    next: {
      page: "1",
      pageSize: "1"
    },
    prev: {}
  },
  objects: [
    {
      name: "2022 Fall Theses",
      uuid: "1eb8d02b-3cf7-459c-a37f-dc2122a75a1f",
      logo: "http://localhost:8080/server/api/core/bitstreams/00a76cd4-08dc-4c53-96f8-2111c110c6b4/content"
    }
  ],
  count: "2"
}
```