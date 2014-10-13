# Introduction

This plugin enables one to use SAML authentication with `fkooman/rest`. 

# Configuation

You need to configure Apache as a SAML SP and configure it. See the 
References section for a link to generic installation instructions. The example
below worked while developing `fkooman/rest`:

    <Location />
        MellonEnable "info"
        MellonSPPrivateKeyFile /etc/httpd/mellon/https_example.org_saml.key
        MellonSPCertFile /etc/httpd/mellon/https_example.org_saml.cert
        MellonSPMetadataFile /etc/httpd/mellon/https_example.org_saml.xml
        MellonIdPMetadataFile /etc/httpd/mellon/https_openidp_feide_no.xml
        MellonEndpointPath /saml
    </Location>

    <Location /app>
        MellonEnable "auth"
    </Location>

# References

- [https://github.com/UNINETT/mod_auth_mellon/wiki/GenericSetup](https://github.com/UNINETT/mod_auth_mellon/wiki/GenericSetup)
