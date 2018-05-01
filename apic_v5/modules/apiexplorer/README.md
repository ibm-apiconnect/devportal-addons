# API Explorer
This is a sample module for IBM API Connect Developer Portal v5.0.8.x (where x >= 3).

It replaces the standard way an API is displayed in the portal with the API Explorer code from APIC v2018. The API Explorer is a ReactJS based application for rendering OpenAPI documents. It has a different look and feel and is much faster due to only displaying a single operation at a time. For large APIs this can give more of a feeling of space and can make it much easier to find what you're looking for as an API Consumer.

## Installation

See the README.txt in the explorer sub directory for instructions on how to obtain the apiexplorer code from npmjs.
Once that has been downloaded into place then zip up this `apiexplorer` directory including all files and subdirectories and install the module as standard using the Developer Portal Admin web interface 

[Knowledge Center: Installing additional modules](https://www.ibm.com/support/knowledgecenter/en/SSMNED_5.0.0/com.ibm.apic.devportal.doc/tapim_portal_additional_modules.html)

Once the module is enabled it will be used as the way to display APIs in the portal, there is no further configuration needed.

### Note: This module requires APIC v5.0.8.3+.
