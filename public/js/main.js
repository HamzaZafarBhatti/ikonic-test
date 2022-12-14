var skeletonId = 'skeleton';
var contentId = 'content';
var skipCounter = 0;
var limit = 10;

function ajaxGet(url, type, onSuccessFunction) {
  $.ajax({
    url: url,
    type: type,
    dataType: 'json',
    beforeSend: beforeSend,
    success: function (response) {
      onSuccessFunction(response)
    }
  })
}

function ajaxPost(url, type, data, onSuccessFunction) {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  })
  $.ajax({
    url: url,
    type: type,
    data: data,
    dataType: 'json',
    success: function (response) {
      onSuccessFunction(response)
    }
  })
}


function getRequests(mode) {
  ajaxGet(`/get-sent-request?limit=${limit}&skip=${skipCounter}&mode=${mode}`, 'get', renderIndex)
}

function getMoreRequests(mode) {
  // Optional: Depends on how you handle the "Load more"-Functionality
  // your code here...
}

function getConnections() {
  // your code here...
}

function getMoreConnections() {
  // Optional: Depends on how you handle the "Load more"-Functionality
  // your code here...
}

function getConnectionsInCommon(userId, connectionId) {
  // your code here...
}

function getMoreConnectionsInCommon(userId, connectionId) {
  // Optional: Depends on how you handle the "Load more"-Functionality
  // your code here...
}

function getSuggestions() {
  ajaxGet(`/get-suggested-connections?limit=${limit}&skip=${skipCounter}`, 'get', renderIndex)
}

function renderIndex(response) {
  $("#skeleton").addClass('d-none')
  $("#content").empty()
  $("#content").html(response['data'])
  $("#content").removeClass('d-none')

  response['count'] < limit ? $("#load_more_btn_parent").addClass('d-none') : $("#load_more_btn_parent").removeClass('d-none')
}

function reRenderIndex(response) {
  $("#skeleton").addClass('d-none')
  $("#content").append(response)
  $("#content").removeClass('d-none')
  let displayedSuggestions = $("#content > div").length;
  console.log(displayedSuggestions)
  displayedSuggestions == 100 ? $("#load_more_btn_parent").addClass('d-none') : $("#load_more_btn_parent").removeClass('d-none');
}

function postRequestSuccess(response) {
  alert(response.message)
  if(response.status == 1) {
    getSuggestions()
  }
}

function getMoreSuggestions() {
  let displayedSuggestions = $("#content > div").length;
  ajaxGet(`/get-suggested-connections?limit=${limit}&skip=${displayedSuggestions}`, 'get', reRenderIndex)
}

function sendRequest(suggestionId) {
  let data = {
    suggestionId: suggestionId
  }
  ajaxPost(`/send-connection-request`, 'post', data, postRequestSuccess)
}

function deleteRequest(userId, requestId) {
  // your code here...
}

function acceptRequest(userId, requestId) {
  // your code here...
}

function removeConnection(userId, connectionId) {
  // your code here...
}

function beforeSend() {
  $("#content").addClass('d-none')
  $("#skeleton").removeClass('d-none')
}

$(function () {
  $('#load_more_btn').attr('onclick', 'getMoreSuggestions()')
  getSuggestions();
});