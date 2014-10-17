%global composer_vendor  fkooman
%global composer_project rest

%global github_owner     fkooman
%global github_name      php-lib-rest

Name:       php-%{composer_vendor}-%{composer_project}
Version:    0.5.2
Release:    1%{?dist}
Summary:    Simple PHP library for writing REST services

Group:      System Environment/Libraries
License:    ASL 2.0
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/archive/%{version}.tar.gz
BuildArch:  noarch

Provides:   php-composer(%{composer_vendor}/%{composer_project}) = %{version}

Requires:   php >= 5.3.3
Requires:   php-password-compat >= 1.0.0
Requires:   php-composer(fkooman/json) >= 0.5.1
Requires:   php-composer(fkooman/json) < 0.6.0

%description
Library written in PHP to make it easy to develop REST applications.

%prep
%setup -qn %{github_name}-%{version}

%build

%install
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/php
cp -pr src/* ${RPM_BUILD_ROOT}%{_datadir}/php

%files
%defattr(-,root,root,-)
%dir %{_datadir}/php/%{composer_vendor}/Http
%dir %{_datadir}/php/%{composer_vendor}/Rest
%{_datadir}/php/%{composer_vendor}/Http/*
%{_datadir}/php/%{composer_vendor}/Rest/*
%doc README.md CHANGES.md COPYING composer.json

%changelog
* Fri Oct 17 2014 François Kooman <fkooman@tuxed.net> - 0.5.2-1
- update to 0.5.2

* Sun Oct 12 2014 François Kooman <fkooman@tuxed.net> - 0.5.1-1
- update to 0.5.1

* Sun Oct 12 2014 François Kooman <fkooman@tuxed.net> - 0.5.0-1
- update to 0.5.0

* Thu Oct 09 2014 François Kooman <fkooman@tuxed.net> - 0.4.11-1
- update to 0.4.11

* Mon Oct 06 2014 François Kooman <fkooman@tuxed.net> - 0.4.10-1
- update to 0.4.10

* Tue Sep 30 2014 François Kooman <fkooman@tuxed.net> - 0.4.9-1
- update to 0.4.9

* Mon Sep 29 2014 François Kooman <fkooman@tuxed.net> - 0.4.8-1
- update to 0.4.8
