# ECA Autopost Facebook

---

This module provides an **ECA (Event - Condition - Action)** action that allows you to automatically publish website content to a Facebook Page.

For news sites or blogs, a large portion of traffic often comes from social media. Sharing links to your website on Facebook can be essential for reaching a wider audience. This module, combined with ECA, helps automate that publishing process — saving time, reducing manual effort, and driving more visitors to your site.

## Prerequisites

* A Facebook app with the proper permissions to publish posts to a Facebook Page.
* A Facebook user who is an **administrator** of the Page you want to publish to, with a **long-lived access token** generated.
* ECA and this module installed on your Drupal site.

## Simple Workflow with Articles

In this example, we’ll configure a workflow to automatically post new articles to a Facebook Page.

1. Install **ECA**, **ECA Content** (a submodule of ECA), and this module.
   Additionally, install an ECA modeller of your choice. In this example, we’ll use **ECA Classic Modeller**.
2. Configure this module by providing your **Facebook Page ID** and **access token**.
3. Create a new model in ECA with any name you like, and make sure to activate it.
4. Add an event:

   * **Insert content entity (ECA Content)**, selecting the type **Content: Article**.
   * Save the event.
5. Add an action:

   * **Content: Post Facebook (ECA Autopost Facebook)**.
   * Enter a value in **Message to post** and/or **Attached link**. You can use plain text or tokens.
   * Save the action.
6. *(Optional)* Add a condition to determine whether or not the article should be posted.
7. Edit the event created in step 4 and add a **successor**.

   * This successor should include the **Content: Post Facebook** action from step 5.
   * *(Optional)* Add a condition here as well.
   * Save the event.
8. Create a new Article on your site and check whether it was successfully published on your Facebook Page.
