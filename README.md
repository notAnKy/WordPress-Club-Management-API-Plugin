# WordPress-Club-Management-API-Plugin

A WordPress plugin that provides a REST API service for managing clubs, club members, and club owners. The plugin integrates with the WordPress database to create and manage clubs, add and remove members, and manage club owner details. 

## Features

- **Database Tables**: The plugin creates four new tables in the WordPress database:
  - **clubs**: Stores information about the clubs.
  - **keys**: Stores API keys for authentication.
  - **members**: Stores information about club members.
  - **trash**: Stores information about deleted items.
  
- **REST API Endpoints**: The plugin provides various endpoints for managing clubs and members:
  - `generate-key`: Generates a new API key.
  - `add-club`: Adds a new club.
  - `update-club`: Updates an existing club's information.
  - `delete-club`: Deletes a club.
  - `get-all-clubs-with-members`: Retrieves all clubs and their associated members.
  - `get-members-by-club`: Retrieves members of a specific club.
  - `get-club-owners`: Retrieves the owners of all clubs.
  - `get-club-owner-details`: Retrieves the details of a specific club owner.
  - `edit-club-owner`: Modifies the details of a club owner.
  - `delete-club-owner`: Deletes a club owner.
  - `add-member`: Adds a new member to a club.
  - `delete-member`: Deletes a member from a club.
  - `update-member`: Updates the information of a club member.

## Database Structure

The plugin adds the following four tables to the WordPress database:
1. **clubs**: Stores club information like name, description, etc.
2. **keys**: Stores API keys for authenticating API requests.
3. **members**: Stores details about club members.
4. **trash**: Stores information about deleted clubs, members, or owners.

## Admin/User Interface

When activated, the plugin creates the necessary database tables and sets up the API. The admin and users can interact with the API via **Postman** or any HTTP client by using the following steps:

### API Authentication

Before making any requests, a valid **API key** must be generated. The API key is used to authenticate requests. If the API key is invalid or missing, the plugin will return an error message.

### Example Usage

1. **Generate API Key**:
   - Endpoint: `generate-key`
   - Method: POST
   - Description: Generates a new API key.

2. **Add Club**:
   - Endpoint: `add-club`
   - Method: POST
   - Description: Adds a new club. Requires club information in the request body.

3. **Update Club**:
   - Endpoint: `update-club`
   - Method: POST
   - Description: Updates an existing club's details (e.g., name, description).

4. **Delete Club**:
   - Endpoint: `delete-club`
   - Method: POST
   - Description: Deletes a specified club.

5. **Get All Clubs with Members**:
   - Endpoint: `get-all-clubs-with-members`
   - Method: GET
   - Description: Retrieves a list of all clubs along with their members.

6. **Get Members by Club**:
   - Endpoint: `get-members-by-club`
   - Method: GET
   - Description: Retrieves all members of a specific club.

7. **Get Club Owners**:
   - Endpoint: `get-club-owners`
   - Method: GET
   - Description: Retrieves all club owners.

8. **Get Club Owner Details**:
   - Endpoint: `get-club-owner-details`
   - Method: GET
   - Description: Retrieves the details of a specific club owner.

9. **Edit Club Owner**:
   - Endpoint: `edit-club-owner`
   - Method: POST
   - Description: Updates the details of a specific club owner.

10. **Delete Club Owner**:
    - Endpoint: `delete-club-owner`
    - Method: POST
    - Description: Deletes a club owner from the system.

11. **Add Member**:
    - Endpoint: `add-member`
    - Method: POST
    - Description: Adds a new member to a club.

12. **Delete Member**:
    - Endpoint: `delete-member`
    - Method: POST
    - Description: Deletes a specific member from the club.

13. **Update Member**:
    - Endpoint: `update-member`
    - Method: POST
    - Description: Updates a member's information.

## Installation

1. Download the plugin files and upload them to the `wp-content/plugins` directory of your WordPress installation.
2. Activate the plugin through the WordPress admin panel.
3. The plugin will automatically create the necessary database tables when activated.

## API Key Authentication

To make requests, you must generate an API key using the `generate-key` endpoint. Include this key in your API requests to authenticate them. Here is an example of how to include the key in the request header:

```bash
Authorization: Bearer YOUR_API_KEY
