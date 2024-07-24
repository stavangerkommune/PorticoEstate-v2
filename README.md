# Api

New Api based on [Slim framework](https://www.slimframework.com/)

## Instructions to run the code

Follow these steps to get started:

1. **Clone the code**
	- Use your preferred method to clone the repository to your local machine.

2. **Configure the database**
	- Update the database configuration settings as per your environment.

3. **Build the image**
	- Set the `INSTALL_XDEBUG` environment variable and build your Docker image:
	  ```bash
	  export INSTALL_XDEBUG=true
	  export INSTALL_MSSQL=true
	  docker compose build
	  ```

4. **Start the server**
	- Launch your containers in detached mode:
	  ```bash
	  docker compose up -d
	  ```

5. **Install required 3rd-party libraries**
	- First-time: Execute the following command to install dependencies inside the `portico_api` container:
	  ```bash
	  docker exec -it portico_api composer install
	  ```

6. **Access the API**
	- The API will be available at [http://localhost:8088/](http://localhost:8088/)

**Note:** Ensure the `xdebug.client_port` is set to `9003`.

## Generate minified js for bookingfrontend
1. Navigate to `src/modules/bookingfrontend/js/bookingfrontend_2`
2. Run `npm install`
3. Run `npm run prod` to build for production