# Code Injection

This plugin allows you to effortlessly create custom ads for your website. Inject code snippets in HTML, CSS, and JavaScript, write and run custom plugins on-the-fly, and take your website's capabilities to the next level.

## Usage
To use this plugin, follow these steps:

- Activate the plugin from the Plugins menu in your dashboard.

- Click on the Code button in the dashboard menu to create and manage your code snippets.

- To insert a code snippet in your post or page, copy the CID (code identifier) from the list and use the shortcode `[inject id='#']`, where `#` is the CID.

- To insert a code snippet in your sidebar, drag and drop the CI widget into the desired widget area and select the code snippet from the dropdown menu.

## Advanced Usage
If you want to run PHP scripts in your website, you need to do some extra steps:

- Go to `Settings > Code` Injection and create a strong activator key. This key will allow you to run PHP scripts safely and securely.

- To insert a PHP script in your post or page, use the shortcode `[unsafe key='#']`, where `#` is the activator key you created before. Write your PHP script between the opening and closing tags of the shortcode.

- To run a PHP script as a plugin, check the “as plugin” checkbox when creating or editing a code snippet. This will execute your PHP script on every page load.

>**Note:** Be careful when running PHP scripts, as they can cause errors or conflicts with other plugins or themes. If you encounter any problems, you can disable the code snippet from the database by changing its status to “draft” in the wp_posts table.