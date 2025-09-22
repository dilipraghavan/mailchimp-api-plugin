# Mailchimp API Integration

A lightweight WordPress plugin for seamless integration with the Mailchimp marketing platform. It allows users to subscribe to your mailing list directly from your website using a simple shortcode form.

---

## ✨ Features

- **Effortless Integration:** Connect your WordPress site to Mailchimp in minutes.
- **Secure Credentials:** API keys and list IDs can be stored securely via environment variables or `wp-config.php` constants.
- **RESTful Subscription:** Utilizes the WordPress REST API for a smooth, no-page-reload subscription experience.
- **Honeypot Spam Protection:** Includes a simple honeypot field to deter automated spam submissions.
- **Double Opt-in Support:** Respect user privacy with an optional double opt-in feature.
- **Comprehensive Logging:** Logs all subscription events to a custom database table for easy monitoring and reporting.
- **Admin Dashboard:** View subscription reports and export data to a CSV file directly from the WordPress admin panel.

---

## 🛠 Installation

There are two methods for installation depending on whether you are an end-user or a developer.

### For End-Users (Packaged Plugin)

To install a ready-to-use version of the plugin, download the latest release from the [Releases page](https://github.com/dilipraghavan/mailchimp-api-plugin/releases). This version is pre-packaged with all dependencies included.

1.  Download the `.zip` file from the latest release.
2.  In the WordPress dashboard, go to **Plugins > Add New**.
3.  Click **Upload Plugin**, select the downloaded `.zip` file, and click **Install Now**.
4.  After installation, click **Activate Plugin**.

### For Developers (with Composer)

This is the recommended method for developers who want to work with the source code or contribute to the plugin.

1.  **Clone the Repository:** Clone the plugin from GitHub to your local machine using Git.
    ```bash
    git clone https://github.com/dilipraghavan/mailchimp-api-plugin.git wp-content/plugins/mailchimp-api-plugin
    ```
2.  **Install Dependencies:** Navigate into the cloned folder from your command line and run Composer to install the required libraries.
    ```bash
    cd mailchimp-api-plugin
    composer install
    ```
3.  **Create ZIP Archive:** Create a `.zip` archive of the entire `mailchimp-api-plugin` folder. This zip file now contains all the necessary plugin files, including the `vendor` directory.
4.  **Upload to WordPress:** In the WordPress dashboard, go to **Plugins > Add New**, click **Upload Plugin**, and select the `.zip` file you just created.
5.  **Activate Plugin:** After installation, click **Activate Plugin**.

## ⚙️ Configuration

### Step 1: Mailchimp Credentials

You can provide your Mailchimp API key and List ID using one of three methods. The plugin will check them in the following order of priority:

1.  **`wp-config.php` constants (Recommended)**:
    ```php
    define('MAILCHIMP_API_KEY', 'YOUR_API_KEY_HERE');
    define('MAILCHIMP_LIST_ID', 'YOUR_LIST_ID_HERE');
    ```
2.  **Environment Variables**:
    Set `MAILCHIMP_API_KEY` and `MAILCHIMP_LIST_ID` in your server's environment.
3.  **WordPress Admin Settings**:
    Navigate to **Settings > Mailchimp API Integration** in your WordPress dashboard and enter the keys.

### Step 2: Test Connection

On the settings page, click the **Test Connection** button to verify that your credentials are correct and the plugin can communicate with the Mailchimp API.

### Step 3: Configure Double Opt-in and Logging

On the same settings page, you can choose whether to enable double opt-in (recommended) and turn logging on or off.

---

## 🚀 Usage

Simply add the following shortcode to any post, page, or widget to display the subscription form:

```bash
[mc_subscribe_form]
```

### Shortcode Attributes

You can customize the form using these optional attributes:

- `consent_label`: Change the text for the consent checkbox.
  - Example: `[mc_subscribe_form consent_label="I agree to receive awesome emails!"]`
- `button_text`: Change the text on the submit button.
  - Example: `[mc_subscribe_form button_text="Sign Me Up!"]`

---

## 📊 Viewing Reports

To view detailed logs of all subscription attempts, navigate to **Settings > MC Reports**. You can filter events by type, HTTP code, and date range, and export the data to a CSV file.

---

## 🤝 Contributing

Contributions are welcome! If you find a bug or have a suggestion, please open an issue or submit a pull request on the [GitHub repository](https://github.com/dilipraghavan/mailchimp-api-plugin).

---

## 📝 License

This project is licensed under the MIT License. See the [LICENSE](https://github.com/dilipraghavan/mailchimp-api-plugin/blob/main/LICENSE) file for details.
