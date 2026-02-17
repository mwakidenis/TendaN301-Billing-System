define(function (require, exports, module) {
  var pageModule = new PageLogic({
    getUrl: "goform/getQos",
    modules: "localhost,onlineList,macFilter",
    setUrl: "goform/setQos",
  });
  ((pageModule.modules = []), (module.exports = pageModule));
  var netCrlModule = new (function () {
    var timeFlag,
      dataChanged,
      refreshDataFlag = !0,
      that = this;
    function createOnlineList(obj) {
      var onlineListlen,
        prop,
        localhostIP,
        hostname,
        nativeHost,
        i = 0,
        k = 0,
        str = "";
      ((localhostIP = obj.localhost.localhost),
        (obj.onlineList = reCreateObj(obj.onlineList, "qosListIP", "up")));
      for (var i = 0; i < obj.onlineList.length; i++) {
        if (obj.onlineList[i].qosListIP == localhostIP) {
          var local = obj.onlineList[i];
          (obj.onlineList.splice(i, 1), obj.onlineList.unshift(local));
          break;
        }
      }
      for (
        obj.macFilterList = reCreateObj(
          obj.macFilter.macFilterList,
          "mac",
          "up",
        ),
          onlineListlen = obj.onlineList.length,
          macFilterListLen = obj.macFilterList.length,
          i = 0;
        i < onlineListlen;
        i++
      ) {
        for (prop in ((str = "<tr class='addListTag'>"), obj.onlineList[i])) {
          ((hostname =
            "" != obj.onlineList[i].qosListRemark
              ? obj.onlineList[i].qosListRemark
              : obj.onlineList[i].qosListHostname),
            (nativeHost = localhostIP == obj.onlineList[i].qosListIP),
            localhostIP == obj.onlineList[i].qosListIP
              ? obj.onlineList[i].qosListIP + _("Local")
              : obj.onlineList[i].qosListIP);
          var manufacturer = translateManufacturer(
            obj.onlineList[i].qosListManufacturer,
          );
          if ("qosListHostname" == prop) {
            ((str += "<td>"),
              (str +=
                "<div class='col-xs-3 col-sm-3 col-md-2 col-lg-2'>" +
                manufacturer +
                "</div>"),
              (str +=
                '<div class="col-xs-9 col-sm-9 col-md-10 col-lg-9" style="margin-top:10px;"><div class="col-xs-10 span-fixed deviceName" style="height:24px;"></div>'),
              (str += '<div class="col-xs-10 none">'),
              (str +=
                ' <input type="text" class="form-control setDeviceName" style="height:24px;padding: 3px 12px;" value="" maxLength="63">'),
              (str += "</div>"),
              (str +=
                '<div class="col-xs-2 row"> <span class="ico-small icon-edit"></span> </div>'),
              (str += "</td>"));
          } else {
            if ("qosListMac" == prop) {
              ((str +=
                '<td class="span-fixed hidden-max-sm" style="width:10%;text-align:center">'),
                (str +=
                  '<span data-target="qosListMac">' +
                  obj.onlineList[i][prop].toUpperCase() +
                  "</span>"),
                (str += "</td>"));
            } else {
              if ("qosListAccess" == prop) {
                if ("pass" == obj.macFilter.curFilterMode) {
                  $("#onlineListHead")
                    .find(".connectPermit")
                    .css("display", "none");
                  continue;
                }
                ($("#onlineListHead").find(".connectPermit").css("display", ""),
                  (str +=
                    '<td style="text-align:center" class="internet-ctl">'),
                  (str += nativeHost
                    ? "<div class='nativeHost'>" + _("Local") + "</div>"
                    : "<div class='switch icon-toggle-on'></div>"),
                  (str += "</td>"));
              }
            }
          }
        }
        ((str += "</tr>"),
          $("#qosList").append(str),
          $("#qosList .addListTag").find(".deviceName").text(hostname),
          $("#qosList .addListTag").find(".deviceName").attr("title", hostname),
          $("#qosList .addListTag").find(".setDeviceName").val(hostname),
          $("#qosList .addListTag")
            .find(".setDeviceName")
            .attr("data-mark", obj.onlineList[i].qosListHostname));
        var upperMac = obj.onlineList[i].qosListMac.toUpperCase();
        ($("#qosList .addListTag").find(".setDeviceName").attr("alt", upperMac),
          (that.onlineListData[upperMac] = obj.onlineList[i]),
          $("#qosList").find(".addListTag").removeClass("addListTag"));
      }
      for (k = 0; k < macFilterListLen; k++) {
        "pass" != obj.macFilterList[k].filterMode &&
          ((str = "<tr class='addListTag'>"),
          (str += "<td class='deviceName'><div class='col-xs-11 span-fixed'>"),
          (str += "</div></td>"),
          (str +=
            "<td class='hidden-max-sm' data-target='mac' data-mac='" +
            obj.macFilterList[k].mac.toUpperCase() +
            "' style='width:10%;text-align:center'>" +
            obj.macFilterList[k].mac.toUpperCase() +
            "</td>"),
          (str += "<td style='text-align:center'>"),
          (str +=
            '<input type="button" class="del btn" value="' +
            _("Unlimit") +
            '">'),
          (str += "</td>"),
          (str += "</tr>"),
          $("#qosListAccess").append(str),
          (hostname =
            "" != obj.macFilterList[k].remark
              ? obj.macFilterList[k].remark
              : obj.macFilterList[k].hostname),
          $("#qosListAccess .addListTag")
            .find(".deviceName div")
            .text(hostname),
          $("#qosListAccess .addListTag")
            .find(".deviceName div")
            .attr("data-host", hostname),
          $("#qosListAccess .addListTag")
            .find(".deviceName")
            .attr("data-mark", obj.macFilterList[k].hostname),
          $("#qosListAccess").find(".addListTag").removeClass("addListTag"));
      }
      ($("#qosDeviceCount").html("(" + $("#qosList").children().length + ")"),
        0 == $("#qosList").children().length &&
          ((str = "<tr><td colspan='2'>" + _("No device") + "</td></tr>"),
          $("#qosList").append(str)),
        $("#blockedDeviceCount").html(
          "(" + $("#qosListAccess").children().length + ")",
        ),
        0 == $("#qosListAccess").children().length &&
          ((str = "<tr><td colspan='2'>" + _("No device") + "</td></tr>"),
          $("#qosListAccess").append(str)),
        "pass" == obj.macFilter.curFilterMode
          ? $("#blockedDevices").addClass("none")
          : $("#blockedDevices").removeClass("none"),
        (that.curFilterMode = obj.macFilter.curFilterMode),
        top.mainLogic.initModuleHeight());
    }
    function updateTable(obj) {
      checkIsTimeOut(obj) && top.location.reload(!0);
      try {
        obj = $.parseJSON(obj);
      } catch (e) {
        obj = {};
      }
      if (isEmptyObject(obj)) {
        top.location.reload(!0);
      } else {
        if (pageModule.pageRunning && !dataChanged) {
          var newMac,
            getOnlineList = obj.onlineList,
            $onlineTbodyList = $("#qosList").children(),
            onlineTbodyLen = $onlineTbodyList.length,
            getOnlineLen = getOnlineList.length,
            j = 0,
            i = 0,
            rowData = new Array(onlineTbodyLen),
            refreshObj = new Array(getOnlineLen),
            newDataArray = [];
          for (i = 0; i < getOnlineLen; i++) {
            for (
              newMac = getOnlineList[i].qosListMac.toUpperCase(),
                that.onlineListData[newMac] = getOnlineList[i],
                refreshObj[i] = {},
                j = 0;
              j < onlineTbodyLen;
              j++
            ) {
              var $input = $onlineTbodyList.eq(j).find("input[data-mark]");
              (($input[0] ? $input.attr("alt").toUpperCase() : "") == newMac &&
                ((rowData[j] = {}),
                (rowData[j].refresh = !0),
                (refreshObj[i].exist = !0)),
                $onlineTbodyList
                  .eq(i)
                  .find("input[data-mark]")
                  .hasClass("edit-old") &&
                  ((rowData[j] = {}), (rowData[j].refresh = !0)));
            }
          }
          for (i = 0; i < getOnlineLen; i++) {
            refreshObj[i].exist || newDataArray.push(getOnlineList[i]);
          }
          for (j = 0; j < onlineTbodyLen; j++) {
            (rowData[j] && rowData[j].refresh) ||
              $onlineTbodyList.eq(j).remove();
          }
          ($("#qosListAccess").html(""),
            (obj.onlineList = newDataArray),
            "pass" == obj.macFilter.curFilterMode
              ? $("#blockedDevices").addClass("none")
              : $("#blockedDevices").removeClass("none"),
            createOnlineList(obj));
        }
      }
    }
    function editDeviceName() {
      var deviceName = $(this).parent().prev().prev().text(),
        reMarkMaxLength = "";
      ($(this).parent().prev().prev().hide(),
        $(this).parent().hide(),
        $(this).parent().prev().show(),
        $(this).parent().prev().find("input").addClass("edit-old"),
        (reMarkMaxLength = $(this)
          .parent()
          .prev()
          .find("input")
          .attr("maxLength")),
        $(this)
          .parent()
          .prev()
          .find("input")
          .val(deviceName.substring(0, reMarkMaxLength)),
        $(this).parent().prev().find("input").focus());
    }
    function clickAccessInternet() {
      var className = this.className;
      if ("switch icon-toggle-on" == className) {
        if (10 <= getBlackLength()) {
          return void top.mainLogic.showModuleMsg(
            _("A maximum of %s devices can be added to the blacklist.", [10]),
          );
        }
        this.className = "switch icon-toggle-off";
      } else {
        this.className = "switch icon-toggle-on";
      }
    }
    ((this.data = {}),
      (this.onlineListData = {}),
      (this.curFilterMode = ""),
      (this.moduleName = "qosList"),
      (this.init = function () {
        ((dataChanged = !1), this.initEvent());
      }),
      (this.initEvent = function () {
        ($("#qosList").delegate(".icon-edit", "click", editDeviceName),
          $("#qosList").delegate(
            ".icon-toggle-on, .icon-toggle-off",
            "click",
            clickAccessInternet,
          ),
          $("#qosList").delegate(".edit-old", "blur", function () {
            ($(this).parent().prev().attr("title", $(this).val()),
              $(this).parent().prev().text($(this).val()),
              $(this).parent().hide(),
              $(this).parent().prev().show(),
              $(this).parent().next().show());
          }),
          $("#qosListAccess").delegate(".del", "click.dd", function (evnet) {
            evnet || window.event;
            ($(this).parent().parent().remove(), (dataChanged = !0));
          }),
          $("#qosList").delegate(".setDeviceName", "keyup", function () {
            var deviceVal = this.value.replace("\t", "").replace("\n", ""),
              len = deviceVal.length,
              totalByte = getStrByteNum(deviceVal);
            if (63 < totalByte) {
              for (var i = len - 1; 0 < i; i--) {
                if ((totalByte -= getStrByteNum(deviceVal[i])) <= 63) {
                  this.value = deviceVal.slice(0, i);
                  break;
                }
              }
            }
            this.value = deviceVal;
          }));
      }),
      (this.initValue = function () {
        ((this.data = pageModule.data),
          (timeFlag = setTimeout(function () {
            !(function refreshTableList() {
              $.get(
                "goform/getQos?" +
                  getRandom() +
                  encodeURIComponent("&modules=localhost,onlineList,macFilter"),
                updateTable,
              );
              if (!refreshDataFlag || dataChanged) {
                return void clearTimeout(timeFlag);
              }
              clearTimeout(timeFlag);
              timeFlag = setTimeout(function () {
                refreshTableList();
              }, 5000);
              pageModule.pageRunning || clearTimeout(timeFlag);
            })();
          }, 5000)),
          $("#qosList").html(""),
          $("#qosListAccess").html(""),
          createOnlineList(this.data));
      }),
      (this.checkData = function () {
        var $td,
          $listTable = $("#qosList").children(),
          length = $listTable.length,
          i = 0;
        if (!(1 == length && $listTable.eq(0).children().length < 2)) {
          for (i = 0; i < length; i++) {
            if (
              (($td = $listTable.eq(i).children()),
              "" == $td.find("input[data-mark]").val().replace(/[ ]/g, ""))
            ) {
              return _("No space is allowed in a password.");
            }
          }
        }
      }),
      (this.getSubmitData = function () {
        var listArray = (function () {
            var hostTitle,
              $listTable = $("#qosListAccess").children(),
              length = $listTable.length,
              i = 0,
              tmpList = [];
            if (1 == length && $listTable.eq(0).children().length < 2) {
              return tmpList;
            }
            for (i = 0; i < length; i++) {
              var tmpObj = {};
              ((tmpObj.hostname = $listTable
                .eq(i)
                .find("td[data-mark]")
                .attr("data-mark")),
                (hostTitle = $listTable
                  .eq(i)
                  .children()
                  .eq(0)
                  .find("div")
                  .attr("data-host")),
                tmpObj.hostname == hostTitle
                  ? (tmpObj.remark = "")
                  : (tmpObj.remark = hostTitle),
                (tmpObj.mac = $listTable
                  .eq(i)
                  .find("[data-target='mac']")
                  .attr("data-mac")),
                (tmpObj.upLimit = "0"),
                (tmpObj.downLimit = "0"),
                (tmpObj.access = "false"),
                tmpList.push(tmpObj));
            }
            return tmpList;
          })().concat(
            (function () {
              var $tr,
                $listTable = $("#qosList").children(),
                length = $listTable.length,
                tmpList = [],
                i = 0;
              if (1 == length && $listTable.eq(0).children().length < 2) {
                return tmpList;
              }
              for (i = 0; i < length; i++) {
                var tmpObj = {};
                if (
                  (($tr = $listTable.eq(i)),
                  (tmpObj.hostname = $tr.find("input[data-mark]").val()),
                  $tr.find("input[data-mark]").val() ==
                  $tr.find("input[data-mark]").attr("data-mark")
                    ? (tmpObj.remark = "")
                    : (tmpObj.remark = $tr.find("input[data-mark]").val()),
                  (tmpObj.mac = $tr.find("input[data-mark]").attr("alt")),
                  tmpObj.mac)
                ) {
                  var upperMac = tmpObj.mac.toUpperCase();
                  ((tmpObj.upLimit =
                    that.onlineListData[upperMac].qosListUpLimit),
                    (tmpObj.downLimit =
                      that.onlineListData[upperMac].qosListDownLimit));
                }
                ($tr
                  .find(".internet-ctl")
                  .children()
                  .hasClass("icon-toggle-on") ||
                $tr.find(".internet-ctl").children().hasClass("nativeHost") ||
                "pass" == that.curFilterMode
                  ? (tmpObj.access = "true")
                  : (tmpObj.access = "false"),
                  tmpList.push(tmpObj));
              }
              return tmpList;
            })(),
          ),
          onlineListStr = departList("true"),
          offlineListStr = departList("false"),
          onlineObj = {},
          offlineObj = {};
        return (
          (onlineObj = { module1: "onlineList", onlineList: onlineListStr }),
          (offlineObj = {
            module2: "macFilter",
            macFilterList: offlineListStr,
          }),
          "pass" == that.curFilterMode
            ? objToString(onlineObj)
            : objToString(onlineObj) + "&" + objToString(offlineObj)
        );
        function departList(type) {
          var i = 0,
            tmpStr = "";
          for (i = 0; i < listArray.length; i++) {
            listArray[i].access == type &&
              ((tmpStr += listArray[i].hostname + "\t"),
              (tmpStr += listArray[i].remark + "\t"),
              (tmpStr += listArray[i].mac + "\t"),
              (tmpStr += listArray[i].upLimit + "\t"),
              (tmpStr += listArray[i].downLimit + "\t"),
              (tmpStr += listArray[i].access + "\n"));
          }
          return tmpStr.replace(/[\n]$/, "");
        }
      }));
  })();
  function getBlackLength() {
    var index = 0,
      i = 0,
      $listTable = $("#qosList").children(),
      length = $listTable.length,
      $blackTable = $("#qosListAccess").children(),
      blackLength = $blackTable.length;
    if (1 == length && $listTable.eq(0).children().length < 2) {
    } else {
      for (i = 0; i < length; i++) {
        $listTable.eq(i).find(".switch").hasClass("icon-toggle-off") && index++;
      }
    }
    for (i = 0; i < blackLength; i++) {
      $blackTable.eq(i).find(".deviceName").html() && index++;
    }
    return index;
  }
  (pageModule.modules.push(netCrlModule),
    (pageModule.beforeSubmit = function () {
      return (
        !(10 < getBlackLength()) ||
        (top.mainLogic.showModuleMsg(
          _("A maximum of %s devices can be added to the blacklist.", [10]),
        ),
        !1)
      );
    }));
});
