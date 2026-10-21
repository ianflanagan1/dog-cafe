# Favourites, in Dog Cafe

## Pages

### Favourites

`/favs` · only people logged in

Saved favourites: the venues you've marked as favourites, listed page by page. You reach it from the Favourite Venues link on your Account page, and you must be logged in. Each venue shows its type and opening times; if you haven't saved any, it says No results.

- Shows: “Saved favourites”, “Page”
- Can show: “No results”

## Things people do

### Saving a venue to favourites

`/api/v1/favs` · only people logged in

Tapping the empty heart on a venue page while logged in saves the venue to your favourites. The heart fills in straight away, and the venue then appears on your Saved favourites page.

### Removing a venue from favourites

`/api/v1/favs/<ext id>` · only people logged in

Tapping the filled heart on a venue page while logged in removes the venue from your favourites. The heart empties straight away, and the venue no longer appears on your Saved favourites page.
