%global composer_vendor  fkooman
%global composer_project rest

%global github_owner     fkooman
%global github_name      php-lib-rest

Name:       php-%{composer_vendor}-%{composer_project}
Version:    1.0.2
Release:    2%{?dist}
Summary:    Simple PHP library for writing REST services

Group:      System Environment/Libraries
License:    ASL 2.0
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/archive/%{version}.tar.gz
Source1:    %{name}-autoload.php

BuildArch:  noarch

Provides:   php-composer(%{composer_vendor}/%{composer_project}) = %{version}

Requires:   php(language) >= 5.3.3
Requires:   php-mbstring
Requires:   php-pcre
Requires:   php-session
Requires:   php-spl
Requires:   php-standard
Requires:   php-composer(fkooman/json) >= 1.0.0
Requires:   php-composer(fkooman/json) < 2.0.0
Requires:   php-composer(symfony/class-loader)

Buildrequires:  php-composer(fkooman/json) >= 1.0.0
Buildrequires:  php-composer(fkooman/json) < 2.0.0
BuildRequires:  php-composer(symfony/class-loader)
BuildRequires:  %{_bindir}/phpunit
BuildRequires:  %{_bindir}/phpab

%description
Library written in PHP to make it easy to develop REST applications.

%prep
%setup -qn %{github_name}-%{version}
# FIXME: two namespaces are exposed, we need to split the code in two packages
cp %{SOURCE1} src/%{composer_vendor}/Rest/autoload.php

%build

%install
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/php
cp -pr src/* ${RPM_BUILD_ROOT}%{_datadir}/php

%check
%{_bindir}/phpab --output tests/bootstrap.php tests
echo 'require "%{buildroot}%{_datadir}/php/%{composer_vendor}/Rest/autoload.php";' >> tests/bootstrap.php
%{_bindir}/phpunit \
    --bootstrap tests/bootstrap.php

%files
%defattr(-,root,root,-)
%dir %{_datadir}/php/%{composer_vendor}/Http
%dir %{_datadir}/php/%{composer_vendor}/Rest
%{_datadir}/php/%{composer_vendor}/Http/*
%{_datadir}/php/%{composer_vendor}/Rest/*
%doc README.md CHANGES.md composer.json
%license COPYING

%changelog
* Wed Sep 02 2015 François Kooman <fkooman@tuxed.net> - 1.0.2-2
- add autoloader
- run tests during build

* Wed Aug 05 2015 François Kooman <fkooman@tuxed.net> - 1.0.2-1
- update to 1.0.2

* Mon Jul 20 2015 François Kooman <fkooman@tuxed.net> - 1.0.1-1
- update to 1.0.1

* Sun Jul 19 2015 François Kooman <fkooman@tuxed.net> - 1.0.0-1
- update to 1.0.0
