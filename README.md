IERU Organic.Edunet APIs
================

About
-----
This project contains two APIs for the Organic.Edunet project:
* **Organic Lingua:** this API takes care of reciving search requests (and formatting the output making local searched for resource info), users (login, register, etc.), and retrieving specific resource information. Check the API documentation in the proper deliverable of the Organic.Lingua project.
* **Analytics Service:** This API is in charge of making search requests to services (unformatted), translate words of phrases (mostly using Xerox and Microsoft Translator, but it is easy to hook up with other services) and registering the requests. Check the API documentation in the proper deliverable of the Organic.Lingua project.

How to use the APIs
-------------------
First of all, you have to download the following github project
*[IERU Rest Engine](https://github.com/ieru/ieru-rest-engine)

Both projects will have to be in the same folder until we finish the composer.json file to simplify the deployment. The files of these projects follows the PSR-0 standar for namespacing structure.

Also, the APIs have a Config folder. Check those files and change the parameters as needed (mostly the MySQL username/password, etc.).

In Development
--------------
There is a third project involved, and is related to an implementation as a relational database of the IEEE LOM standard. It is located here: [IERU Rest Engine](https://github.com/ieru/ieru-rest-engine), and is used for inserting the data of an IEEE LOM xml file into the database. Organic.Lingua API uses such a database for the resources thrown by the searches.