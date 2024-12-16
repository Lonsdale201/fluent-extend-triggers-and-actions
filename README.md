# Fluent Extend Triggers and Actions
Extra triggers, actions and others for WooCommerce, Wordpress, JetFormBuilder, Jetreviews

## Desc

This plugin provides additional add-ons to FluentCRM.

7 triggers are currently available:

* JetFormBuilder
* JetReview
* WooCommerce
* Standard WordPress

> [!IMPORTANT]
> Minimum PHP version: 7.4

> [!IMPORTANT]
> FluentCRM minimum version: 2.8.0

> [!IMPORTANT]
> FluentCRM Pro minimum version: 2.8.0

> [!IMPORTANT]
> Minimum WordPress version: 6.0

> [!Note]
> The plugin supports at the moment Jetformbuilder, Jetreviews, Woocommerce for the Triggers

> [!WARNING]
To use the plugin, you need **Fluentcrm**. For the WooCommerce specific functionality, you also need **Fluentcrm pro**.

## How to download?

In the right section the big button: <>Code click, and in the dropdown menu, select the Download ZIP option. The downloaded file just upload it, you can easily install the downloaded file as an plugin within wordpress.

## Features

### Triggers

The plugin add seven Triggers with JetFormbuilder, Jetreview, and WooCommerce

* Advanced New Order (Created) | WooCommerce
* JetReview Event | JetRreview
* JetFormBuilder Post Insert | JetFormBuilder
* JetFormBuilder Form Submission | JetFormBuilder
* JetFormBuilder Update User | JetFormBuilder
* User Role Changed | WordPress
* Review Added | WooCommerce

**In the Jetreview, At the moment I can't authenticate the guest properly in the reviews, even though I have the contact in the crm. We will come back to it later.**


### Actions

A new action has now been added. It is called **Update posts**. This is an independent action. You have the possibility to select one of several post types (cpt) and to which status the automation should change it. You can choose to apply to all posts or to all posts except the last one.
![image](https://github.com/Lonsdale201/fluent-extend-triggers-and-actions/assets/23199033/9d18cad1-94a4-4686-9560-20934daa4b28)

### SmartCodes

Nine smartcodes are available. These have been added to a new post category, and six with the JetReview category.
![image](https://github.com/Lonsdale201/fluent-extend-triggers-and-actions/assets/23199033/b4c25727-695a-49e3-9f2a-a81fd91ab5de)


### JetEngine Macros

Currently these macros are provided by the plugin you can use in the JetEngine Query Builder / Users query type:

* Fluent CRM / WP Users
* Fluent CRM Users with Contact Type
* Fluent CRM Users with List
* Fluent CRM Users with Status

![image](https://github.com/Lonsdale201/fluent-extend-triggers-and-actions/assets/23199033/9b39ee82-e0cd-4fed-b267-2eeb7c02e4d1)


## Note
We will not stop here. I have a number of ideas for future additions and I'm also interested in what you might need.

## Planned features

- [ ] JetBooking Triggers
- [ ] JetAppointments Triggers 
- [X] JetReview Triggers
- [ ] Data Store / Custom Content type compatibility
- [ ] TutorLMS triggers

---

## CHANGELOG

**V 1.4.1 2024.12.16**

* Fixed the Update user jetformbuilder trigger

---

**V 1.4 2024.12.16**

From now on, you do not need fluentcrm pro, only if you want to use woocommerce specific triggers and actions.

**Improvement**

Better dependency checker method added.

---

**V 1.3 2024.12.14**

* fixed a bug where the default fluent functions (actions, smartcodes) were not available for the Advanced New Order (Created) trigger
* New trigger: User role changed
* Improved exiting **JetFormBuilder Post Insert** trigger with a new form field name / value condition

---

**V 1.2.1 2024.06.16**

* Fixed missing JetFormBuilder trigger registration process

---

**V 1.2 2024.06.16**

* Two new trigger added to  WooCommerce: Advanced new order (Created), Review added
* One new action: Place order

---

**V 1.1 2024.06.15**

* New Triggers For Amelia Booking: Appointben created, Event booked
* New Triggers for the JetReview - both work, submit, or approved
* New Smartcodes for Jetreview

---

**V 1.0 2024.06.15**
Release the kraken
