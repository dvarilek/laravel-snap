# Laravel Snapshot Tree Package

A Laravel package for capturing and persisting the state of Eloquent models along with their relationships, acting as 
an "archive" for models, where specified attributes are copied over to another table as "snapshots".

> [!CAUTION]
> This package is currently in its early stages of development and should not be used in production applications.
> Package wide refactors may happen at any time, introducing breaking changes. 

## Overview
This package allows you to capture the state of Eloquent models and their related models at a specific point in time. 
Unlike simply storing a foreign key reference for relationships, this package lets you capture specific attributes from related models.  

This means, that captured related data remains preserved, even if the original models are modified or deleted. 
It’s particularly useful when dealing with sensitive records, where preserving an accurate and unaltered historical 
state is needed for compliance with legislative/regulatory requirements and auditing purposes.

### Examples of use

+ **Audit trails** 
  + (preserving changes for legislative reasons)
+ **Legal documents** 
  + (preserving changes for legislative and legal reasons)
+ **Medical records** 
  + (storing customers patients name etc...)
+ **Customer transactions** 
  + (transactions details such as customer names, addresses etc...)

  
