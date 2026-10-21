+++
app = "Dog Cafe"
built_from = "eafa776, 2026-10-11"

[tracker]
kind = "jira"
site = "ianflanagan1.atlassian.net"
space = "KAN"
issue_type = "Task"
labels = []

[tracker.stages]
being-worked-on = "In Progress"
+++

# Dog Cafe

What people can see and do in Dog Cafe, in the words its screens use, for anyone describing a problem with it. Built by product-map: build it again rather than editing it.

## On every page

- Links: “Account” (`/settings`), “Login” (`/login?location=`), “List” (`/list`), “Map” (`/map`), “Suggest” (`/suggest`), “Suggest a Feature” (`/suggest`)
- Field: “Search town”
- Button: “☰”
- Can show: “An error page showing only a number: 401, 403, 404, 422, 500”

## Areas

Each area's pages, and the things people do there, are in its file in `areas/`, next to this one, with what's on each page and the messages it can show. The choices the app offers are in `choices.md`.

- Favourites, `areas/favs.md`: Favourites (`/favs`). Saving a venue to favourites, Removing a venue from favourites.
- List, `areas/list.md`: List (`/<town>`, `/list/<town>`).
- Login, `areas/login.md`: Login (`/login`).
- Map, `areas/map.md`: Map (`/map/<town>`).
- Settings, `areas/settings.md`: Settings (`/settings`).
- Suggest a feature, `areas/suggest.md`: Suggest a feature (`/suggest`). Sending a suggestion.
- Venue, `areas/venue.md`: Venue (`/venue/<venue reference>`).
- Other pages and things people do, `areas/other.md`: Error, Suggest a feature. Opening the documentation, Signing in with Google, Signing in with Discord, Logging out, Deleting your account.
