%global composer_vendor  fkooman
%global composer_project rest

%global github_owner     fkooman
%global github_name      php-lib-rest

Name:       php-%{composer_vendor}-%{composer_project}
Version:    0.9.0
Release:    1%{?dist}
Summary:    Simple PHP library for writing REST services

Group:      System Environment/Libraries
License:    ASL 2.0
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/archive/%{version}.tar.gz
BuildArch:  noarch

Provides:   php-composer(%{composer_vendor}/%{composer_project}) = %{version}

Requires:   php(language) >= 5.3.3
Requires:   php-mbstring
Requires:   php-pcre
Requires:   php-session
Requires:   php-spl
Requires:   php-standard

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
%doc README.md CHANGES.md composer.json
%license COPYING

%changelog
* Sun Jun 28 2015 François Kooman <fkooman@tuxed.net> - 0.9.0-1
- update to 0.9.0

* Thu May 28 2015 François Kooman <fkooman@tuxed.net> - 0.8.9-1
- update to 0.8.9

* Thu May 14 2015 François Kooman <fkooman@tuxed.net> - 0.8.8-1
- update to 0.8.8

* Thu May 14 2015 François Kooman <fkooman@tuxed.net> - 0.8.7-1
- update to 0.8.7

* Thu May 14 2015 François Kooman <fkooman@tuxed.net> - 0.8.6-1
- update to 0.8.6

* Sun May 10 2015 François Kooman <fkooman@tuxed.net> - 0.8.5-1
- update to 0.8.5

* Sat May 09 2015 François Kooman <fkooman@tuxed.net> - 0.8.4-1
- update to 0.8.4

* Tue May 05 2015 François Kooman <fkooman@tuxed.net> - 0.8.3-1
- update to 0.8.3

* Tue May 05 2015 François Kooman <fkooman@tuxed.net> - 0.8.2-1
- update to 0.8.2

* Mon Apr 27 2015 François Kooman <fkooman@tuxed.net> - 0.8.1-1
- update to 0.8.1

* Sun Apr 12 2015 François Kooman <fkooman@tuxed.net> - 0.8.0-1
- update to 0.8.0

* Tue Mar 24 2015 François Kooman <fkooman@tuxed.net> - 0.7.5-1
- update to 0.7.5

* Sun Mar 15 2015 François Kooman <fkooman@tuxed.net> - 0.7.4-1
- update to 0.7.4

* Tue Mar 10 2015 François Kooman <fkooman@tuxed.net> - 0.7.0-1
- update to 0.7.0

* Tue Feb 17 2015 François Kooman <fkooman@tuxed.net> - 0.6.8-1
- update to 0.6.8

* Wed Jan 28 2015 François Kooman <fkooman@tuxed.net> - 0.6.5-1
- update to 0.6.5
