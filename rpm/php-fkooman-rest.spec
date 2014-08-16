%global composer_vendor  fkooman
%global composer_project rest

%global github_owner     fkooman
%global github_name      php-lib-rest

Name:       php-%{composer_vendor}-%{composer_project}
Version:    0.4.0
Release:    1%{?dist}
Summary:    Simple PHP library for writing REST services

Group:      Applications/Internet
License:    ASL 2.0
URL:        https://github.com/%{github_owner}/%{github_name}
Source0:    https://github.com/%{github_owner}/%{github_name}/releases/download/%{version}/%{name}-%{version}.tar.xz
BuildArch:  noarch

Provides:   php-composer(%{composer_vendor}/%{composer_project}) = %{version}

Requires:   php >= 5.3.3

Requires:   php-composer(fkooman/json) >= 0.4.0
Requires:   php-composer(fkooman/json) < 0.5.0

%description
Library written in PHP to make it easy to develop REST applications.

%prep
%setup -q

%build

%install
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/php/%{composer_vendor}/Http
mkdir -p ${RPM_BUILD_ROOT}%{_datadir}/php/%{composer_vendor}/Rest
cp -pr src/%{composer_vendor}/Http/* ${RPM_BUILD_ROOT}%{_datadir}/php/%{composer_vendor}/Http
cp -pr src/%{composer_vendor}/Rest/* ${RPM_BUILD_ROOT}%{_datadir}/php/%{composer_vendor}/Rest

%files
%defattr(-,root,root,-)
%dir %{_datadir}/php/%{composer_vendor}/Http
%dir %{_datadir}/php/%{composer_vendor}/Rest
%{_datadir}/php/%{composer_vendor}/Http
%{_datadir}/php/%{composer_vendor}/Rest
%doc README.md CHANGES.md COPYING composer.json

%changelog
* Sat Aug 16 2014 François Kooman <fkooman@tuxed.net> - 0.4.0-1
- initial package
