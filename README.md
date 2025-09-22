# Mailchimp API Integration

A lightweight WordPress plugin for seamless integration with the Mailchimp marketing platform.  
It allows users to subscribe to your mailing list directly from your website using a simple shortcode form.

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

### Method 1: The Easy Way

1. Download the latest release of the plugin from the GitHub repository.
2. Go to your WordPress Dashboard and navigate to **Plugins > Add New > Upload Plugin**.
3. Select the downloaded ZIP file and click **Install Now**.
4. Activate the plugin from your **Plugins** page.

### Method 2: The Developer Way

Clone the repository into your WordPress plugins folder:

````bash
git clone https://github.com/dilipraghavan/mailchimp-api-plugin.git wp-content/plugins/mailchimp-api-plugin
Activate the plugin through the WordPress admin panel.

---

## ⚙️ Configuration

### Step 1: Mailchimp Credentials

You can provide your Mailchimp API key and List ID using one of three methods.
The plugin will check them in the following order of priority:

**Option 1: `wp-config.php` constants (Recommended)**

```php
define('MAILCHIMP_API_KEY', 'YOUR_API_KEY_HERE');
define('MAILCHIMP_LIST_ID', 'YOUR_LIST_ID_HERE');
**Option 2: Environment Variables**
Set `MAILCHIMP_API_KEY` and `MAILCHIMP_LIST_ID` in your server's environment.

**Option 3: WordPress Admin Settings**
Navigate to **Settings > Mailchimp API Integration** in your WordPress dashboard and enter the keys.

---

### Step 2: Test Connection

On the settings page, click the **Test Connection** button to verify that your credentials are correct and the plugin can communicate with the Mailchimp API.

---

### Step 3: Configure Double Opt-in and Logging

On the same settings page, you can enable or disable **Double Opt-in** (recommended) and turn **Logging** on or off.

---

## 🚀 Usage

Simply add the following shortcode to any post, page, or widget to display the subscription form:

```shortcode
[mc_subscribe_form]

📊 Viewing Reports

To view detailed logs of all subscription attempts, navigate to Settings > MC Reports.
You can filter events by type, HTTP code, and date range, and export the data to a CSV file.

🤝 Contributing

Contributions are welcome!
If you find a bug or have a suggestion, please open an issue or submit a pull request on the GitHub repository.

📄 License

This project is licensed under the MIT License.
See the LICENSE
 file for details.
````
