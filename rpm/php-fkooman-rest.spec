%global composer_vendor  fkooman
%global composer_project rest

%global github_owner     fkooman
%global github_name      php-lib-rest

Name:       php-%{composer_vendor}-%{composer_project}
Version:    0.6.7
Release:    1%{?dist}
Summary:    Simple PHP library for writing REST services

Group:      System Environment/Libraries
License:    ASL 2.0
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/archive/%{version}.tar.gz
BuildArch:  noarch

Provides:   php-composer(%{composer_vendor}/%{composer_project}) = %{version}

Requires:   php >= 5.3.3
Requires:   php-composer(fkooman/json) >= 0.6.0
Requires:   php-composer(fkooman/json) < 0.7.0

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
* Sat Feb 07 2015 François Kooman <fkooman@tuxed.net> - 0.6.7-1
- update to 0.6.7

* Wed Feb 04 2015 François Kooman <fkooman@tuxed.net> - 0.6.6-1
- update to 0.6.6

* Wed Jan 28 2015 François Kooman <fkooman@tuxed.net> - 0.6.5-1
- update to 0.6.5
